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

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

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

    private function containsBool(Type $type): bool
    {
        $nonNullableType = TypeCombinator::removeNull($type);

        return $nonNullableType->isBoolean()->yes();
    }
}
