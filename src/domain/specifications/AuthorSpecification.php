<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class AuthorSpecification implements BookSpecificationInterface
{
    public function __construct(
        private string $authorName,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitAuthor($this);
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }
}
