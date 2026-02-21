<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DomainIsCleanRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $namespace = $scope->getNamespace();

        if ($namespace === null || !str_starts_with($namespace, 'app\domain')) {
            return [];
        }

        $nodeFinder = new NodeFinder();
        $originalNode = $node->getOriginalNode();

        /** @var array<StaticCall|StaticPropertyFetch> $usages */
        $usages = $nodeFinder->find($originalNode->stmts, static function (Node $n): bool {
            if ($n instanceof StaticPropertyFetch && $n->class instanceof Name) {
                return $n->class->toString() === 'Yii';
            }

            if ($n instanceof StaticCall && $n->class instanceof Name) {
                return $n->class->toString() === 'Yii';
            }

            return false;
        });

        $errors = [];

        foreach ($usages as $usage) {
            $errors[] = RuleErrorBuilder::message('Domain layer must be clean. Do not use Yii::$app or other static Yii calls.')
                ->identifier('architecture.domainClean')
                ->line($usage->getStartLine())
                ->build();
        }

        return $errors;
    }
}
