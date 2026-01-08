<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class ValueObjectMustBeFinalRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return [];
        }

        $namespace = $scope->getNamespace();

        if ($namespace !== null && str_starts_with($namespace, 'app\domain\values') && !$classReflection->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('Value Object %s must be final.', $classReflection->getName()))
                    ->identifier('architecture.valueObjectFinal')
                    ->build(),
            ];
        }

        return [];
    }
}
