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
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
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
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInProtectedNamespace($scope)) {
            return [];
        }

        if ($node instanceof ClassMethod) {
            return $this->checkClassMethod($node);
        }

        if ($node instanceof Property) {
            return $this->checkProperty($node);
        }

        return [];
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
    private function checkClassMethod(ClassMethod $node): array
    {
        $errors = [];

        foreach ($node->params as $param) {
            if ($param->type === null) {
                continue;
            }

            $forbidden = $this->findForbiddenType($param->type);

            if ($forbidden === null) {
                continue;
            }

            $paramName = $param->var instanceof Variable && is_string($param->var->name)
                ? $param->var->name
                : 'unknown';
            $errors[] = $this->buildError($forbidden, 'parameter', $paramName);
        }

        if ($node->returnType instanceof Node) {
            $forbidden = $this->findForbiddenType($node->returnType);

            if ($forbidden !== null) {
                $errors[] = $this->buildError($forbidden, 'return type', $node->name->toString());
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkProperty(Property $node): array
    {
        if (!$node->type instanceof Node) {
            return [];
        }

        $forbidden = $this->findForbiddenType($node->type);

        if ($forbidden === null) {
            return [];
        }

        $propName = $node->props[0]->name->toString();

        return [$this->buildError($forbidden, 'property', $propName)];
    }

    private function findForbiddenType(ComplexType|Identifier|Name $type): ?string
    {
        if ($type instanceof NullableType) {
            return $this->findForbiddenType($type->type);
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->types as $inner) {
                $found = $this->findForbiddenType($inner);

                if ($found !== null) {
                    return $found;
                }
            }

            return null;
        }

        if ($type instanceof Name) {
            $resolved = $type->toString();

            foreach (self::FORBIDDEN_CLASSES as $forbidden) {
                if (
                    strcasecmp($resolved, $forbidden) === 0
                    || str_ends_with(strtolower($resolved), '\\' . strtolower($this->getClassBasename($forbidden)))
                ) {
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

    private function getClassBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
