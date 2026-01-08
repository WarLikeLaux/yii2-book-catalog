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
final readonly class UseCaseMustBeFinalRule implements Rule
{
    /**
     * Specifies the PHP-Parser node type this rule is applied to.
     *
     * @return string The fully-qualified class name of the node type (`PHPStan\Node\InClassNode`) that this rule processes.
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * Enforces that non-abstract, non-anonymous classes whose names end with "UseCase" are declared final.
     *
     * @param InClassNode $node The class node being analyzed.
     * @param Scope $ _scope The current analysis scope (unused).
     * @return array<int,\PHPStan\Rules\RuleError> An array of rule errors for violations; empty if no issues found.
     */
    public function processNode(Node $node, Scope $_scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return [];
        }

        $className = $classReflection->getName();

        if (str_ends_with($className, 'UseCase') && !$classReflection->isFinal()) {
            return [
                RuleErrorBuilder::message(sprintf('UseCase class %s must be final.', $className))
                    ->identifier('architecture.useCaseFinal')
                    ->build(),
            ];
        }

        return [];
    }
}