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
     * Initialize a QueryResult with the provided models, total count, and optional pagination.
     *
     * @param array<int, T> $models List of result models.
     * @param int $totalCount Total number of items available.
     * @param PaginationDto|null $pagination Pagination details or null if not applicable.
     */
    public function __construct(
        private array $models,
        private int $totalCount,
        private ?PaginationDto $pagination = null,
    ) {
    }

    /**
     * Create an empty QueryResult with the given pagination settings.
     *
     * @param int $page The page number to set on the pagination DTO.
     * @param int $pageSize The page size to set on the pagination DTO.
     * @return self<object> A QueryResult containing an empty models array, total count 0, and a PaginationDto configured with the provided page and page size.
     */
    public static function empty(int $page = 1, int $pageSize = 20): self
    {
        return new self(
            [],
            0,
            new PaginationDto($page, $pageSize, 0, 0),
        );
    }

    /**
     * Gets the models contained in this query result.
     *
     * @return array<int, T> The list of models.
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * Retrieve pagination details for the current result set.
     *
     * @return PaginationDto|null The pagination details, or null if not available.
     */
    public function getPagination(): ?PaginationDto
    {
        return $this->pagination;
    }

    /**
     * Create a new QueryResult with the provided models while preserving total count and pagination.
     *
     * @param array<int, T> $models The list of models to include in the new result.
     * @return self<T> A new QueryResult instance containing the provided models.
     */
    public function withModels(array $models): self
    {
        return new self($models, $this->totalCount, $this->pagination);
    }
}