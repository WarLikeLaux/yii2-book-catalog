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
 */
final readonly class ValueObjectMustBeFinalRule implements Rule
{
    /**
     * Specifies the node type this rule is registered to process.
     *
     * @return string The fully-qualified class name of the node type the rule handles.
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Checks a class node and reports an error if a value-object class in the app\domain\values namespace is not final.
     *
     * @param \PhpParser\Node $node The analyzed class node (an InClassNode carrying class reflection).
     * @param \PHPStan\Analyser\Scope $scope The current analysis scope used to determine the class namespace.
     * @return array An array containing a single RuleError when the class is a non-abstract, non-anonymous value object that is not final, or an empty array otherwise.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return [];
        }

        $namespace = $scope->getNamespace();

        if ($namespace !== null && str_starts_with($namespace, 'app\domain\values') && !$classReflection->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('Value Object %s must be final.', $classReflection->getName()))
                    ->identifier('architecture.valueObjectFinal')
                    ->build(),
            ];
        }

        return [];
    }
}