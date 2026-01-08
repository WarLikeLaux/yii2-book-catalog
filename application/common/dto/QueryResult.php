<?php

declare(strict_types=1);

namespace app\application\common\dto;

use app\application\ports\PagedResultInterface;

/**
 * @template T of object
 * @implements PagedResultInterface<T>
 */
final readonly class QueryResult implements PagedResultInterface
{
    /**
     * @param array<int, T> $models
     */
    public function __construct(
        private array $models,
        private int $totalCount,
        private ?PaginationDto $pagination = null,
    ) {
    }

    /**
     * @return array<int, T>
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
     * @param array<int, T> $models
     * @return self<T>
     */
    public function withModels(array $models): self
    {
        return new self($models, $this->totalCount, $this->pagination);
    }
}
