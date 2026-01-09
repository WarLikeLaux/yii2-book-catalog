<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 * @noRector
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

    public function processNode(Node $_node, Scope $scope): array
    {
        if (!$this->isInProtectedNamespace($scope)) {
            return [];
        }

        $errors = [];
        $classReflection = $scope->getClassReflection();

        if ($classReflection instanceof ClassReflection) {
            foreach ($classReflection->getAncestors() as $ancestor) {
                if (in_array($ancestor->getName(), self::FORBIDDEN_CLASSES, true)) {
                    return [RuleErrorBuilder::message(
                        sprintf(
                            'Extending %s is forbidden in domain and application layers. Use domain entities or DTOs instead.',
                            $ancestor->getName(),
                        ),
                    )->identifier('architecture.noActiveRecord')->build()];
                }
            }

            $nativeReflection = $classReflection->getNativeReflection();

            foreach ($nativeReflection->getMethods() as $method) {
                $errors = [...$errors, ...$this->checkReflectionMethod($method)];
            }

            foreach ($nativeReflection->getProperties() as $property) {
                $errors = [...$errors, ...$this->checkReflectionProperty($property)];
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
    private function checkReflectionMethod(\ReflectionMethod $method): array
    {
        $errors = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            if ($type === null) {
                continue;
            }

            foreach ($this->extractTypeNamesRecursive($type) as $typeName) {
                if (!$this->isForbidden($typeName)) {
                    continue;
                }

                $errors[] = $this->buildError($typeName, 'parameter', $param->getName());
            }
        }

        $returnType = $method->getReturnType();

        if ($returnType !== null) {
            foreach ($this->extractTypeNamesRecursive($returnType) as $typeName) {
                if (!$this->isForbidden($typeName)) {
                    continue;
                }

                $errors[] = $this->buildError($typeName, 'return type', $method->getName());
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkReflectionProperty(\ReflectionProperty $property): array
    {
        $type = $property->getType();

        if ($type === null) {
            return [];
        }

        $errors = [];

        foreach ($this->extractTypeNamesRecursive($type) as $typeName) {
            if (!$this->isForbidden($typeName)) {
                continue;
            }

            $errors[] = $this->buildError($typeName, 'property', $property->getName());
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function extractTypeNamesRecursive(\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            $names = [];

            foreach ($type->getTypes() as $innerType) {
                $names = [...$names, ...$this->extractTypeNamesRecursive($innerType)];
            }

            return array_unique($names);
        }

        return [];
    }

    private function isForbidden(string $className): bool
    {
        foreach (self::FORBIDDEN_CLASSES as $forbidden) {
            if ($this->isForbiddenClass($className, $forbidden)) {
                return true;
            }
        }

        return false;
    }

    private function isForbiddenClass(string $className, string $forbidden): bool
    {
        return strcasecmp(ltrim($className, '\\'), ltrim($forbidden, '\\')) === 0;
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
