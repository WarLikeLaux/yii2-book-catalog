<?php

declare(strict_types=1);

namespace Tools\Rector;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddCodeCoverageIgnoreToFormMethodsRector extends AbstractRector
{
    private const METHODS_TO_IGNORE = ['rules', 'attributeLabels', 'formName', 'loadFromRequest'];
    private const COVERAGE_IGNORE_ATTRIBUTE = 'PHPUnit\\Framework\\Attributes\\CodeCoverageIgnore';

    private PhpAttributeAnalyzer $phpAttributeAnalyzer;

    private bool $hasChanged = false;

    public function __construct(PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
        $this->phpAttributeAnalyzer = $phpAttributeAnalyzer;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add #[CodeCoverageIgnore] attribute to rules() and attributeLabels() methods in Form classes',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
final class BookForm extends Model
{
    public function rules(): array
    {
        return [];
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
final class BookForm extends Model
{
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [];
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): Node|null
    {
        $this->hasChanged = false;

        $className = (string) $this->getName($node);

        if (!str_contains($className, 'Form')) {
            return null;
        }

        foreach ($node->getMethods() as $classMethod) {
            $this->processAddCoverageIgnoreAttribute($classMethod);
        }

        if (!$this->hasChanged) {
            return null;
        }

        return $node;
    }

    private function processAddCoverageIgnoreAttribute(ClassMethod $classMethod): void
    {
        $methodName = $this->getName($classMethod->name);

        if (!in_array($methodName, self::METHODS_TO_IGNORE, true)) {
            return;
        }

        if ($this->phpAttributeAnalyzer->hasPhpAttribute($classMethod, self::COVERAGE_IGNORE_ATTRIBUTE)) {
            return;
        }

        $classMethod->attrGroups[] = new AttributeGroup([
            new Attribute(new FullyQualified(self::COVERAGE_IGNORE_ATTRIBUTE)),
        ]);
        $this->hasChanged = true;
    }
}
