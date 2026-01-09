<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
 */
final readonly class QueryPortsMustReturnDtoRule implements Rule
{
    private const string QUERY_SERVICE_NAMESPACE = 'app\\application\\ports';
    private const string PAGED_RESULT_INTERFACE = 'app\\application\\ports\\PagedResultInterface';
    private const string DTO_SUFFIX = 'Dto';
    private const string APPLICATION_NAMESPACE = 'app\\application\\';
    private const array QUERY_SERVICE_SUFFIXES = ['QueryServiceInterface'];
    private const array FINDER_SEARCHER_SUFFIXES = ['FinderInterface', 'SearcherInterface'];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        $portType = $this->getPortType($classReflection);

        if ($portType === null) {
            return [];
        }

        $errors = [];
        $allowScalars = $portType === 'query_service';

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            $methodReflection = $classReflection->getNativeMethod($method->getName());
            $variants = $methodReflection->getVariants();

            if ($variants === []) {
                continue;
            }

            $returnType = $variants[0]->getReturnType();

            if ($this->isAllowedReturnType($returnType, $allowScalars)) {
                continue;
            }

            $startLine = $method->getStartLine();

            $message = $allowScalars
            ? 'Method %s::%s() must return DTO, scalar, void, or PagedResultInterface. ' .
            'Returning ActiveRecord or untyped array is forbidden.'
            : 'Method %s::%s() must return DTO, void, or PagedResultInterface. ' .
            'Finder/Searcher interfaces must return DTO, not scalar or ActiveRecord.';

            $errors[] = RuleErrorBuilder::message(sprintf($message, $classReflection->getName(), $method->getName()))
                ->identifier('architecture.queryPortReturnType')
                ->line($startLine !== false ? $startLine : $node->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function getPortType(ClassReflection $classReflection): ?string
    {
        if (!$classReflection->isInterface()) {
            return null;
        }

        $className = $classReflection->getName();

        if (!str_starts_with($className, self::QUERY_SERVICE_NAMESPACE)) {
            return null;
        }

        foreach (self::QUERY_SERVICE_SUFFIXES as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return 'query_service';
            }
        }

        foreach (self::FINDER_SEARCHER_SUFFIXES as $suffix) {
            if (str_ends_with($className, $suffix)) {
                return 'finder_searcher';
            }
        }

        return null;
    }

    private function isAllowedReturnType(Type $type, bool $allowScalars): bool
    {
        if ($type->isVoid()->yes() || $type->isNull()->yes()) {
            return true;
        }

        $nonNullableType = TypeCombinator::removeNull($type);

        if ($allowScalars && $this->isScalarType($nonNullableType)) {
            return true;
        }

        $classNames = $nonNullableType->getObjectClassNames();

        if (count($classNames) > 0) {
            return $this->isAllowedObjectType($nonNullableType);
        }

        if ($nonNullableType->isArray()->yes() || $nonNullableType->isIterable()->yes()) {
            return $this->isAllowedIterableType($nonNullableType, $allowScalars);
        }

        return false;
    }

    private function isScalarType(Type $type): bool
    {
        return $type->isInteger()->yes()
        || $type->isString()->yes()
        || $type->isBoolean()->yes()
        || $type->isFloat()->yes();
    }

    private function isAllowedObjectType(Type $type): bool
    {
        $classNames = $type->getObjectClassNames();

        foreach ($classNames as $className) {
            if ($className === self::PAGED_RESULT_INTERFACE) {
                return true;
            }

            if (
                str_starts_with($className, self::APPLICATION_NAMESPACE)
                && str_ends_with($className, self::DTO_SUFFIX)
            ) {
                return true;
            }
        }

        return count($classNames) === 0;
    }

    private function isAllowedIterableType(Type $type, bool $allowScalars): bool
    {
        $iterableValueType = $type->getIterableValueType();

        if ($allowScalars && $this->isScalarType($iterableValueType)) {
            return true;
        }

        if ($iterableValueType->isObject()->yes()) {
            return $this->isAllowedObjectType($iterableValueType);
        }

        return false;
    }
}
