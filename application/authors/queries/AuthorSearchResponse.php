<?php

declare(strict_types=1);

namespace app\application\authors\queries;

readonly class AuthorSearchResponse
{
    /** @param AuthorReadDto[] $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $pageSize
    ) {
    }
}
