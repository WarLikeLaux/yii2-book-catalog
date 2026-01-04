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
final readonly class DomainEntitiesMustBePureRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isAnonymous()) {
            return [];
        }

        $namespace = $scope->getNamespace();

        if ($namespace === null || !str_starts_with($namespace, 'app\domain\entities')) {
            return [];
        }

        if ($node->extends !== null) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Domain Entity %s must be pure and not extend any class. It currently extends %s.',
                    (string)$node->name,
                    $node->extends->toString()
                ))
                    ->identifier('architecture.domainEntityPure')
                    ->build(),
            ];
        }

        return [];
    }
}
