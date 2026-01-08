<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
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
    /**
     * Specifies which node type this rule is applied to.
     *
     * @return string The fully-qualified class name of the node this rule processes (InClassNode::class).
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Reports static `Yii` usages found inside class nodes that belong to the domain layer.
     *
     * Only inspects classes when the surrounding namespace starts with `app\domain` and returns a rule error for each detected `StaticCall` or `StaticPropertyFetch` whose class name is `Yii`.
     *
     * @param Node $node The class node to inspect (expected original node to contain statements).
     * @param Scope $scope The current PHPStan scope used to determine the namespace.
     * @return \PHPStan\Rules\RuleError[] Array of rule errors, one per detected static `Yii` usage. 
     */
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
            if ($n instanceof StaticPropertyFetch && $n->class instanceof Node\Name) {
                return $n->class->toString() === 'Yii';
            }

            if ($n instanceof StaticCall && $n->class instanceof Node\Name) {
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