<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use DateTime;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DisallowDateTimeRule implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->class instanceof Name)) {
            return [];
        }

        if ($scope->resolveName($node->class) === DateTime::class) {
            return [
                RuleErrorBuilder::message('Use DateTimeImmutable instead of DateTime.')
                    ->identifier('architecture.dateTime')
                    ->build(),
            ];
        }

        return [];
    }
}
