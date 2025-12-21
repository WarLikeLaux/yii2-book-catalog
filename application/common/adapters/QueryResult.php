<?php

declare(strict_types=1);

namespace app\application\common\adapters;

use app\application\common\dto\PaginationDto;
use app\interfaces\QueryResultInterface;

final class QueryResult implements QueryResultInterface
{
    public function __construct(
        private readonly array $models,
        private readonly int $totalCount,
        private readonly ?PaginationDto $pagination = null
    ) {
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getPagination(): ?PaginationDto
    {
        return $this->pagination;
    }
}
