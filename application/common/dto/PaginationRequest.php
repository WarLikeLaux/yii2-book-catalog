<?php

declare(strict_types=1);

namespace app\application\common\dto;

readonly class PaginationRequest
{
    protected const int DEFAULT_PAGE = 1;
    protected const int DEFAULT_LIMIT = 20;
    protected const int MAX_LIMIT = 100;

    public int $page;
    public int $limit;

    final public function __construct(mixed $page, mixed $limit, int $defaultLimit = self::DEFAULT_LIMIT)
    {
        $p = is_numeric($page) ? (int) $page : static::DEFAULT_PAGE;
        $this->page = max(1, $p);

        $l = is_numeric($limit) ? (int) $limit : $defaultLimit;
        $this->limit = min(static::MAX_LIMIT, max(1, $l));
    }
}
