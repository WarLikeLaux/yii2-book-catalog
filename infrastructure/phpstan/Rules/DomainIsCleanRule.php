<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticPropertyFetch>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DomainIsCleanRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticPropertyFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->class instanceof Node\Name)) {
            return [];
        }

        if ($node->class->toString() !== 'Yii') {
            return [];
        }

        $namespace = $scope->getNamespace();
        if ($namespace !== null && str_starts_with($namespace, 'app\domain')) {
            return [
                RuleErrorBuilder::message('Domain layer must be clean. Do not use Yii::$app or other static Yii calls.')
                    ->identifier('architecture.domainClean')
                    ->build(),
            ];
        }

        return [];
    }
}
