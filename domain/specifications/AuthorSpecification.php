<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class AuthorSpecification implements BookSpecificationInterface
{
    public function __construct(
        private string $authorName,
    ) {
    }

    /**
     * @return array{type: string, value: mixed}
     */
    public function toSearchCriteria(): array
    {
        return [
            'type' => 'author',
            'value' => $this->authorName,
        ];
    }
}
