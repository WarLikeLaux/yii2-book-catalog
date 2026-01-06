<?php

declare(strict_types=1);

namespace app\application\common\dto;

use app\application\ports\PagedResultInterface;

final readonly class QueryResult implements PagedResultInterface
{
    /**
     * @param array<int, object> $models
     */
    public function __construct(
        private array $models,
        private int $totalCount,
        private ?PaginationDto $pagination = null,
    ) {
    }

    /**
     * @return array<int, object>
     */
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

    /**
     * @param array<int, object> $models
     */
    public function withModels(array $models): self
    {
        return new self($models, $this->totalCount, $this->pagination);
    }
}
