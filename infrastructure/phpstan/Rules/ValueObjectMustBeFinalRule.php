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
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class ValueObjectMustBeFinalRule implements Rule
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

        $namespace = $scope->getNamespace();
        if ($namespace !== null && str_starts_with($namespace, 'app\domain\values') && !$node->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('Value Object %s must be final.', (string)$node->name))
                    ->identifier('architecture.valueObjectFinal')
                    ->build(),
            ];
        }

        return [];
    }
}
