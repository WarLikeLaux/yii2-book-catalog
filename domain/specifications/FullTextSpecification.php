<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class FullTextSpecification implements BookSpecificationInterface
{
    public function __construct(
        private string $query,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitFullText($this);
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
