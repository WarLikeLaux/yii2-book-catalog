<?php

declare(strict_types=1);

namespace app\application\common\dto;

use app\application\ports\PagedResultInterface;

final readonly class QueryResult implements PagedResultInterface
{
    /**
     * @param array<mixed> $models
     */
    public function __construct(
        private array $models,
        private int $totalCount,
        private ?PaginationDto $pagination = null,
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
