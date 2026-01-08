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

    /**
     * Specifies the PHP-Parser node class this PHPStan rule applies to.
     *
     * @return string The fully-qualified node class name that this rule targets (InClassNode).
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Validates methods declared on query port interfaces and returns rule errors for methods with disallowed return types.
     *
     * Examines the provided class node; if the class is an application query port (QueryServiceInterface, FinderInterface,
     * or SearcherInterface under the application ports namespace), each method declared by the class is checked for an
     * allowed return type. For query services scalars are permitted in addition to DTOs, void, and PagedResultInterface;
     * for finder/searcher interfaces scalars and ActiveRecord types are disallowed. For each offending method a
     * PHPStan RuleError is produced (identifier `architecture.queryPortReturnType`) and returned.
     *
     * @param Node $node The class node being analyzed (expected to be an InClassNode wrapping the inspected class).
     * @param Scope $scope The current PHPStan analysis scope.
     * @return array<int, \PHPStan\Rules\RuleError> Array of RuleError objects describing return-type violations, empty if none.
     */
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

    /**
     * Determine the port type for an interface based on its fully-qualified name.
     *
     * @param ClassReflection $classReflection Reflection of the class or interface to inspect.
     * @return string|null `'query_service'` if the interface is in the `app\application\ports` namespace and its name ends with a value from `QUERY_SERVICE_SUFFIXES`; `'finder_searcher'` if it ends with a value from `FINDER_SEARCHER_SUFFIXES`; `null` if the reflection is not an interface, not in the target namespace, or does not match any suffix.
     */
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

    /**
     * Determines whether a given PHPStan Type is an allowed return type for query port methods.
     *
     * Considers `void` and `null` allowed. After removing `null`, allows scalar types when `$allowScalars` is true.
     * Object types are allowed only if they represent a DTO within the application namespace or the configured
     * paged result interface. Array/iterable types are allowed only when their value types meet the same rules.
     *
     * @param Type $type The PHPStan type to validate.
     * @param bool $allowScalars When true, integer/string/float/boolean types (and iterables of those) are permitted.
     * @return bool `true` if the type is permitted as a return type for the evaluated port method, `false` otherwise.
     */
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

    /**
     * Determines whether the given type represents a PHP scalar: integer, string, boolean, or float.
     *
     * @param Type $type The type to inspect.
     * @return bool `true` if the type is integer, string, boolean, or float; `false` otherwise.
     */
    private function isScalarType(Type $type): bool
    {
        return $type->isInteger()->yes()
            || $type->isString()->yes()
            || $type->isBoolean()->yes()
            || $type->isFloat()->yes();
    }

    /**
     * Determines whether an object type is permitted as a query port return.
     *
     * @param Type $type The analyzed object type.
     * @return bool `true` if the type is the paged result interface, a DTO class in the application namespace (class name ends with `Dto`), or has no specific class names; `false` otherwise.
     */
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

    /**
     * Check whether an iterable type's element type is allowed for query port returns.
     *
     * @param Type $type The iterable type whose value type will be validated.
     * @param bool $allowScalars Whether scalar element types are permitted.
     * @return bool `true` if the iterable's value type is allowed (DTO, `PagedResultInterface`, or scalar when permitted), `false` otherwise.
     */
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