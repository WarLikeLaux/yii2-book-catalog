<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class NoActiveRecordInDomainOrApplicationRule implements Rule
{
    private const array FORBIDDEN_CLASSES = [
        'yii\\db\\ActiveRecord',
        'yii\\base\\Model',
    ];
    private const array PROTECTED_NAMESPACES = [
        'app\\domain',
        'app\\application',
    ];

    / **
     * Specifies which AST node type this rule applies to.
     *
     * @return string The fully qualified class name of the node type this rule processes.
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Checks a class node inside protected namespaces for usage of forbidden ActiveRecord-like classes
     * and returns rule errors for violations found in parent class inheritance, property types,
     * method parameter types, and method return types.
     *
     * @param Node $node The InClassNode being analyzed (original class statement is inspected).
     * @param Scope $scope The current scope used to resolve names and determine the namespace.
     * @return \PHPStan\Rules\RuleError[] An array of rule errors describing each violation; empty if none.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInProtectedNamespace($scope)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        $errors = [];

        if ($originalNode instanceof Node\Stmt\Class_ && $originalNode->extends instanceof Name) {
            $parentClass = $scope->resolveName($originalNode->extends);

            foreach (self::FORBIDDEN_CLASSES as $forbidden) {
                $normalizedForbidden = ltrim($forbidden, '\\');
                $normalizedParent = ltrim($parentClass, '\\');

                if (strcasecmp($normalizedParent, $normalizedForbidden) === 0) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            'Extending %s is forbidden in domain and application layers. Use domain entities or DTOs instead.',
                            $forbidden,
                        ),
                    )
                        ->identifier('architecture.noActiveRecordInCore')
                        ->build();
                    break;
                }
            }
        }

        foreach ($originalNode->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $errors = [...$errors, ...$this->checkClassMethod($stmt, $scope)];
            } elseif ($stmt instanceof Property) {
                $errors = [...$errors, ...$this->checkProperty($stmt, $scope)];
            }
        }

        return $errors;
    }

    /**
     * Determines whether the given scope is located within a protected namespace.
     *
     * Checks if the scope's namespace begins with any of the configured protected namespaces.
     *
     * @param Scope $scope The PHPStan scope to inspect.
     * @return bool `true` if the scope's namespace starts with a protected namespace, `false` otherwise.
     */
    private function isInProtectedNamespace(Scope $scope): bool
    {
        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return false;
        }

        foreach (self::PROTECTED_NAMESPACES as $protected) {
            if (str_starts_with($namespace, $protected)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans a class method's parameters and return type for forbidden types and returns corresponding rule errors.
     *
     * For each parameter and the return type that reference a forbidden class, an IdentifierRuleError is produced
     * indicating the offending forbidden class and the offending parameter or method name.
     *
     * @param ClassMethod $node The method AST node to inspect.
     * @param Scope $scope The PHPStan scope used to resolve type names.
     * @return list<IdentifierRuleError> A list of rule errors for each detected forbidden type usage.
     */
    private function checkClassMethod(ClassMethod $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->params as $param) {
            if ($param->type === null) {
                continue;
            }

            $forbidden = $this->findForbiddenType($param->type, $scope);

            if ($forbidden === null) {
                continue;
            }

            $paramName = $param->var instanceof Variable && is_string($param->var->name)
                ? $param->var->name
                : 'unknown';
            $errors[] = $this->buildError($forbidden, 'parameter', $paramName);
        }

        if ($node->returnType instanceof Node) {
            $forbidden = $this->findForbiddenType($node->returnType, $scope);

            if ($forbidden !== null) {
                $errors[] = $this->buildError($forbidden, 'return type', $node->name->toString());
            }
        }

        return $errors;
    }

    /**
         * Checks a class property for forbidden types and produces a rule error if one is found.
         *
         * @param Property $node The property AST node to inspect.
         * @param Scope $scope  The current analysis scope used to resolve type names.
         * @return list<IdentifierRuleError> A list containing a single IdentifierRuleError when the property's type references a forbidden class, or an empty list otherwise.
         */
    private function checkProperty(Property $node, Scope $scope): array
    {
        if (!$node->type instanceof Node) {
            return [];
        }

        $forbidden = $this->findForbiddenType($node->type, $scope);

        if ($forbidden === null) {
            return [];
        }

        $propName = $node->props[0]->name->toString();

        return [$this->buildError($forbidden, 'property', $propName)];
    }

    /**
     * Resolve a PHP-Parser type node and identify the first forbidden class it represents, if any.
     *
     * Handles nullable, union, and intersection types by inspecting their inner types and uses the provided
     * scope to resolve named types to fully-qualified class names for comparison against the forbidden list.
     *
     * @param ComplexType|Identifier|Name $type The parsed type node to inspect.
     * @param Scope $scope The analysis scope used to resolve Name nodes to fully-qualified names.
     * @return string|null The fully-qualified forbidden class name if a match is found, or `null` if none match.
     */
    private function findForbiddenType(ComplexType|Identifier|Name $type, Scope $scope): ?string
    {
        if ($type instanceof NullableType) {
            return $this->findForbiddenType($type->type, $scope);
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->types as $inner) {
                $found = $this->findForbiddenType($inner, $scope);

                if ($found !== null) {
                    return $found;
                }
            }

            return null;
        }

        if ($type instanceof Name) {
            $resolvedName = $scope->resolveName($type);

            foreach (self::FORBIDDEN_CLASSES as $forbidden) {
                if (strcasecmp($resolvedName, $forbidden) === 0) {
                    return $forbidden;
                }
            }
        }

        return null;
    }

    /**
     * Create an IdentifierRuleError describing forbidden usage of a class in a specific context.
     *
     * @param string $forbiddenClass Fully-qualified name of the forbidden class (e.g. `yii\db\ActiveRecord`).
     * @param string $context Context where the forbidden class was found (e.g. "parameter", "return type", "property").
     * @param string $name The name of the element where the forbidden class was used (parameter name, method name, or property name).
     * @return IdentifierRuleError An error describing the violation with the identifier `architecture.noActiveRecordInCore`.
     */
    private function buildError(string $forbiddenClass, string $context, string $name): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Using %s in %s "%s" is forbidden in domain and application layers. Use domain entities or DTOs instead.',
                $forbiddenClass,
                $context,
                $name,
            ),
        )
            ->identifier('architecture.noActiveRecordInCore')
            ->build();
    }
}