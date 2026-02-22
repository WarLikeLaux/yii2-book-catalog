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
final readonly class NoGhostQueryServiceInApplicationRule implements Rule
{
    private const string APPLICATION_NAMESPACE = 'app\\application\\';
    private const string QUERY_SERVICE_SUFFIX = 'QueryService';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isInterface() || $classReflection->isAnonymous()) {
            return [];
        }

        $className = $classReflection->getName();

        if (!str_starts_with($className, self::APPLICATION_NAMESPACE)) {
            return [];
        }

        if (!str_ends_with($className, self::QUERY_SERVICE_SUFFIX)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Ghost QueryService in Application layer is forbidden. Class %s must not exist in application. Query Services belong only in infrastructure/queries/.',
                    $className,
                ),
            )
                ->identifier('architecture.noGhostQueryService')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
