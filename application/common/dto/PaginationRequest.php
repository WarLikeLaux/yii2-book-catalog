<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class PaginationRequest
{
    private const int DEFAULT_PAGE = 1;
    private const int DEFAULT_LIMIT = 20;
    private const int MAX_LIMIT = 100;

    public int $page;

    public int $limit;

    public function __construct(mixed $page, mixed $limit)
    {
        $p = is_numeric($page) ? (int) $page : self::DEFAULT_PAGE;
        $this->page = max(1, $p);

        $l = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;
        $this->limit = min(self::MAX_LIMIT, max(1, $l));
    }
}
