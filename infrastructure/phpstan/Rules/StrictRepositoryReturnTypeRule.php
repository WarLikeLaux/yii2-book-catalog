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
final readonly class StrictRepositoryReturnTypeRule implements Rule
{
    private const string REPOSITORY_NAMESPACE = 'app\\application\\ports';
    private const string REPOSITORY_SUFFIX = 'RepositoryInterface';

    /**
     * Specifies the kind of AST node this rule is intended to analyze.
     *
     * @return string The fully qualified class name of the node type this rule targets.
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Analyze an InClassNode and report repository interface methods that violate the repository return-type policy.
     *
     * Only methods declared directly on repository interfaces (identified by namespace and suffix) are inspected.
     * For each such method that has a boolean return type (after removing nullability), a RuleError is produced
     * with identifier `architecture.repositoryReturnBool` and the method's starting line.
     *
     * @param \PhpParser\Node $node The class node to analyze (expected to be an InClassNode).
     * @param \PHPStan\Analyser\Scope $scope The current analysis scope.
     * @return \PHPStan\Rules\RuleError[] Array of rule errors for methods that return boolean.
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!$this->isRepositoryInterface($classReflection)) {
            return [];
        }

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

            $returnType = $variants[0]->getReturnType();

            if (!$this->containsBool($returnType)) {
                continue;
            }

            $startLine = $method->getStartLine();

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Method %s::%s() returns bool which is forbidden in Repository interfaces. ' .
                    'Repositories should return entities, throw exceptions, or return void. ' .
                    'Move existence checks to QueryService.',
                    $classReflection->getName(),
                    $method->getName(),
                ),
            )
                ->identifier('architecture.repositoryReturnBool')
                ->line($startLine !== false ? $startLine : $node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * Determines whether a class reflection represents a repository interface used by the application ports.
     *
     * @param ClassReflection $classReflection The class reflection to examine.
     * @return bool `true` if the reflection is an interface whose FQN starts with `self::REPOSITORY_NAMESPACE` and ends with `self::REPOSITORY_SUFFIX`, `false` otherwise.
     */
    private function isRepositoryInterface(ClassReflection $classReflection): bool
    {
        if (!$classReflection->isInterface()) {
            return false;
        }

        $name = $classReflection->getName();

        if (!str_starts_with($name, self::REPOSITORY_NAMESPACE)) {
            return false;
        }

        return str_ends_with($name, self::REPOSITORY_SUFFIX);
    }

    /**
     * Determines whether the given type represents `bool` after removing nullability.
     *
     * @param Type $type The type to inspect.
     * @return bool `true` if the type (ignoring `null`) is boolean, `false` otherwise.
     */
    private function containsBool(Type $type): bool
    {
        $nonNullableType = TypeCombinator::removeNull($type);

        return $nonNullableType->isBoolean()->yes();
    }
}