<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DomainIsCleanRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof StaticPropertyFetch && ($node->class instanceof Node\Name && $node->class->toString() === 'Yii')) {
            return $this->buildError($scope);
        }

        if ($node instanceof StaticCall && ($node->class instanceof Node\Name && $node->class->toString() === 'Yii')) {
            return $this->buildError($scope);
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function buildError(Scope $scope): array
    {
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
