<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class PaginationDto
{
    public function __construct(
        public int $page,
        public int $pageSize,
        public int $totalCount,
        public int $totalPages
    ) {
    }
}
