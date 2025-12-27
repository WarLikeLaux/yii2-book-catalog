<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 * @codeCoverageIgnore
 */
final readonly class UseCaseMustBeFinalRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isAbstract() || $node->isAnonymous()) {
            return [];
        }

        $className = (string)$node->name;

        if (str_ends_with($className, 'UseCase') && !$node->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('UseCase class %s must be final.', $className))
                    ->identifier('architecture.useCaseFinal')
                    ->build(),
            ];
        }

        return [];
    }
}
