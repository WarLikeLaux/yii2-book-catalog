<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 * @noRector
 * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
 */
final readonly class NoActiveRecordInDomainOrApplicationRule implements Rule
{
    private const array FORBIDDEN_CLASSES = [
        'yii\db\ActiveRecord',
        'yii\db\BaseActiveRecord',
        'yii\base\Model',
    ];
    private const array PROTECTED_NAMESPACES = [
        'app\domain',
        'app\application',
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInProtectedNamespace($scope)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if (!$classReflection instanceof ClassReflection) {
            return [];
        }

        $ancestorErrors = $this->checkAncestors($classReflection);

        if ($ancestorErrors !== []) {
            return $ancestorErrors;
        }

        return [
            ...$this->checkMethods($classReflection, $node),
            ...$this->checkProperties($classReflection, $node),
        ];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkAncestors(ClassReflection $classReflection): array
    {
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

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkMethods(ClassReflection $classReflection, Node $node): array
    {
        $errors = [];

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            $methodReflection = $classReflection->getNativeMethod($method->getName());
            $variants = $methodReflection->getVariants();

            if ($variants === []) {
                continue;
            }

            $startLine = $method->getStartLine();
            $lineNumber = $startLine !== false ? $startLine : $node->getStartLine();

            foreach ($variants[0]->getParameters() as $param) {
                $paramType = $param->getType();
                $forbiddenClass = $this->findForbiddenClass($paramType);

                if ($forbiddenClass === null) {
                    continue;
                }

                $errors[] = $this->buildError($forbiddenClass, 'parameter', $param->getName(), $lineNumber);
            }

            $returnType = $variants[0]->getReturnType();
            $forbiddenClass = $this->findForbiddenClass($returnType);

            if ($forbiddenClass === null) {
                continue;
            }

            $errors[] = $this->buildError($forbiddenClass, 'return type', $method->getName(), $lineNumber);
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkProperties(ClassReflection $classReflection, Node $node): array
    {
        $errors = [];

        foreach ($classReflection->getNativeReflection()->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            if (!$classReflection->hasNativeProperty($property->getName())) {
                continue;
            }

            $propertyReflection = $classReflection->getNativeProperty($property->getName());
            $propertyType = $propertyReflection->getReadableType();
            $forbiddenClass = $this->findForbiddenClass($propertyType);

            if ($forbiddenClass === null) {
                continue;
            }

            $propertyLine = $node->getStartLine();

            $errors[] = $this->buildError($forbiddenClass, 'property', $property->getName(), $propertyLine);
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
            if ($namespace === $protected || str_starts_with($namespace, $protected . '\\')) {
                return true;
            }
        }

        return false;
    }

    private function findForbiddenClass(Type $type): ?string
    {
        $nonNullableType = TypeCombinator::removeNull($type);
        $classNames = $nonNullableType->getObjectClassNames();

        foreach ($classNames as $className) {
            if ($this->isForbiddenType($className)) {
                return $className;
            }
        }

        if ($nonNullableType->isArray()->yes() || $nonNullableType->isIterable()->yes()) {
            $iterableValueType = $nonNullableType->getIterableValueType();
            $valueClassNames = $iterableValueType->getObjectClassNames();

            foreach ($valueClassNames as $className) {
                if ($this->isForbiddenType($className)) {
                    return $className;
                }
            }
        }

        return null;
    }

    private function isForbiddenType(string $className): bool
    {
        foreach (self::FORBIDDEN_CLASSES as $forbidden) {
            if (strcasecmp($className, $forbidden) === 0) {
                return true;
            }

            if (!$this->reflectionProvider->hasClass($className)) {
                continue;
            }

            if (!$this->reflectionProvider->hasClass($forbidden)) {
                continue;
            }

            $classReflection = $this->reflectionProvider->getClass($className);
            $forbiddenReflection = $this->reflectionProvider->getClass($forbidden);

            if ($classReflection->isSubclassOfClass($forbiddenReflection)) {
                return true;
            }
        }

        return false;
    }

    private function buildError(string $forbiddenClass, string $context, string $name, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Using %s in %s "%s" is forbidden in domain and application layers. Use domain entities or DTOs instead.',
                $forbiddenClass,
                $context,
                $name,
            ),
        )
            ->line($line)
            ->identifier('architecture.noActiveRecordInCore')
            ->build();
    }
}
