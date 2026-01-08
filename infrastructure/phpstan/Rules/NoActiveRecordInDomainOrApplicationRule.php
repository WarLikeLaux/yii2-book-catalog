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

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInProtectedNamespace($scope)) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        $errors = [];

        foreach ($originalNode->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $errors = [...$errors, ...$this->checkClassMethod($stmt, $scope)];
            } elseif ($stmt instanceof Property) {
                $errors = [...$errors, ...$this->checkProperty($stmt, $scope)];
            }
        }

        return $errors;
    }

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
     * @return list<IdentifierRuleError>
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
     * @return list<IdentifierRuleError>
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
