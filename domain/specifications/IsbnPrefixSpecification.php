<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class IsbnPrefixSpecification implements BookSpecificationInterface
{
    public function __construct(
        private string $prefix
    ) {
    }

    public function toSearchCriteria(): array
    {
        return [
            'type' => 'isbn_prefix',
            'value' => $this->prefix,
        ];
    }
}
