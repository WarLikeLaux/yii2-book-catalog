<?php

declare(strict_types=1);

namespace app\application\books\queries;

final readonly class BookSearchCriteria
{
    public function __construct(
        public string $globalSearch = '',
        public int $page = 1,
        public int $pageSize = 20
    ) {
    }
}
