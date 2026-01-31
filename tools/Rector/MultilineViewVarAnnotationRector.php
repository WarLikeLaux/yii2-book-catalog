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
        if (!$this->shouldProcessFile()) {
            return null;
        }

        $comments = $node->getComments();

        if ($comments === []) {
            return null;
        }

        [$newComments, $hasChanged] = $this->processComments($comments);

        if (!$hasChanged) {
            return null;
        }

        // Avoid infinite loops by comparing text
        $oldText = implode('', array_map(static fn($c) => $c->getText(), $comments));
        $newText = implode('', array_map(static fn($c) => $c->getText(), $newComments));

        if ($oldText === $newText) {
            return null;
        }

        $node->setAttribute('comments', $newComments);
        return $node;
    }

    private function shouldProcessFile(): bool
    {
        $filePath = $this->file->getFilePath();
        return str_contains($filePath, '/views/') || str_contains($filePath, '/mail/');
    }

    /**
     * @param \PhpParser\Comment[] $comments
     * @return array{0: \PhpParser\Comment[], 1: bool}
     */
    private function processComments(array $comments): array
    {
        $newComments = [];
        $currentGroup = [];
        $hasChanged = false;

        foreach ($comments as $comment) {
            $text = $comment->getText();

            if (str_starts_with($text, '/**') && str_contains($text, '@var')) {
                $currentGroup = array_merge($currentGroup, $this->extractVarLines($text));
                $hasChanged = true;
            } else {
                if ($currentGroup !== []) {
                    $newComments[] = $this->createCombinedDocBlock($currentGroup);
                    $currentGroup = [];
                }

                $newComments[] = $comment;
            }
        }

        if ($currentGroup !== []) {
            $newComments[] = $this->createCombinedDocBlock($currentGroup);
        }

        return [$newComments, $hasChanged];
    }

    /**
     * @return string[]
     */
    private function extractVarLines(string $text): array
    {
        $lines = explode("\n", $text);
        $extracted = [];

        foreach ($lines as $line) {
            $line = trim($line, "/* \t\r\n");

            if (!str_contains($line, '@var')) {
                continue;
            }

            $extracted[] = $line;
        }

        return $extracted;
    }

    /**
     * @param string[] $lines
     */
    private function createCombinedDocBlock(array $lines): Doc
    {
        // Добавляем \n в конце для отступа от кода
        $text = "/**\n * " . implode("\n * ", $lines) . "\n */\n";
        return new Doc($text);
    }
}
