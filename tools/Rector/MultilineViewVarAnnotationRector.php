<?php

declare(strict_types=1);

namespace Tools\Rector;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Custom rule to convert single-line @var PHPDoc to multi-line format.
 */
final class MultilineViewVarAnnotationRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert single-line @var PHPDoc to multi-line format',
            [
                new CodeSample(
                    '/** @var Book $book */',
                    "/**\n * @var Book \$book\n */",
                ),
            ],
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node::class];
    }

    public function refactor(Node $node): ?Node
    {
        $filePath = $this->file->getFilePath();

        if (!str_contains($filePath, '/views/') && !str_contains($filePath, '/mail/')) {
            return null;
        }

        $comments = $node->getComments();

        if ($comments === []) {
            return null;
        }

        $hasChanged = false;
        $newComments = [];

        foreach ($comments as $comment) {
            $text = $comment->getText();

            if (
                str_starts_with($text, '/**')
                && str_contains($text, '@var')
                && !str_contains($text, "\n")
            ) {
                $content = trim(substr($text, 3, -2));
                $newText = "/**\n * " . $content . "\n */";
                $newComments[] = new Doc($newText);
                $hasChanged = true;
            } else {
                $newComments[] = $comment;
            }
        }

        if ($hasChanged) {
            $node->setAttribute('comments', $newComments);
            return $node;
        }

        return null;
    }
}
