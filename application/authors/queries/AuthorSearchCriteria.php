<?php

declare(strict_types=1);

namespace app\application\authors\queries;

final readonly class AuthorSearchCriteria
{
    public function __construct(
        public string $search = '',
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
