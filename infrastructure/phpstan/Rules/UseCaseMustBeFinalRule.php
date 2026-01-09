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
 * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
 */
final readonly class UseCaseMustBeFinalRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $_scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return [];
        }

        $className = $classReflection->getName();

        if (str_ends_with($className, 'UseCase') && !$classReflection->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('UseCase class %s must be final.', $className))
                    ->identifier('architecture.useCaseFinal')
                    ->line($node->getStartLine())
                    ->build(),
            ];
        }

        return [];
    }
}
