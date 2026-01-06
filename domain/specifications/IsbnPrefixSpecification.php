<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class IsbnPrefixSpecification implements BookSpecificationInterface
{
    public function __construct(
        private string $prefix,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitIsbnPrefix($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
