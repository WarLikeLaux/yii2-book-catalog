<?php

declare(strict_types=1);

namespace app\infrastructure\phpstan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 * @codeCoverageIgnore Логика статического анализа проверяется тестами PHPStan
 */
final readonly class DomainEntitiesMustBePureRule implements Rule
{
    /**
     * Specify the node class this rule applies to.
     *
     * @return string The fully-qualified class name of the node type this rule targets.
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    / **
     * Enforces that classes in the app\domain\entities namespace do not extend other classes.
     *
     * @param InClassNode $node The class node being analyzed.
     * @param Scope $scope The current analysis scope containing namespace information.
     * @return array<int, \PHPStan\Rules\RuleError> An array containing a single RuleError when the inspected domain entity extends a parent class; an empty array otherwise.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAnonymous()) {
            return [];
        }

        $namespace = $scope->getNamespace();

        if ($namespace === null || !str_starts_with($namespace, 'app\domain\entities')) {
            return [];
        }

        $parent = $classReflection->getParentClass();

        if ($parent instanceof ClassReflection) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Domain Entity %s must be pure and not extend any class. It currently extends %s.',
                    $classReflection->getName(),
                    $parent->getName(),
                ))
                    ->identifier('architecture.domainEntityPure')
                    ->build(),
            ];
        }

        return [];
    }
}