<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DisallowYiiTOutsideAdaptersRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->class instanceof Node\Name) || !($node->name instanceof Node\Identifier)) {
            return [];
        }

        if ($node->class->toString() !== 'Yii' || $node->name->toString() !== 't') {
            return [];
        }

        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return [];
        }

        if (
            str_starts_with($namespace, 'app\presentation')
            || str_starts_with($namespace, 'app\infrastructure\adapters')
        ) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Direct Yii::t() calls are allowed only in presentation and infrastructure adapters. Use TranslatorInterface instead.')
                ->identifier('architecture.yiiT')
                ->build(),
        ];
    }
}
