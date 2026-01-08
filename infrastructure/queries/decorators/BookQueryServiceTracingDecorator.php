<?php

declare(strict_types=1);

namespace app\infrastructure\queries\decorators;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\application\ports\TracerInterface;
use app\domain\specifications\BookSpecificationInterface;

final readonly class BookQueryServiceTracingDecorator implements BookQueryServiceInterface
{
    public function __construct(
        private BookQueryServiceInterface $service,
        private TracerInterface $tracer,
    ) {
    }

    public function findById(int $id): ?BookReadDto
    {
        return $this->tracer->trace('BookQuery::' . __FUNCTION__, fn(): ?BookReadDto => $this->service->findById($id));
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        return $this->tracer->trace('BookQuery::' . __FUNCTION__, fn(): ?BookReadDto => $this->service->findByIdWithAuthors($id));
    }

    public function search(string $term, int $page, int $pageSize): PagedResultInterface
    {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->search($term, $page, $pageSize),
        );
    }

    /**
     * Searches for books matching the given specification and returns a paginated result.
     *
     * @param BookSpecificationInterface $specification Criteria used to filter books.
     * @param int $page The pagination page number.
     * @param int $pageSize Number of items per page.
     * @return PagedResultInterface A paginated collection of books that match the specification.
     */
    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $pageSize,
    ): PagedResultInterface {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->searchBySpecification($specification, $page, $pageSize),
        );
    }

    /**
     * Check whether a book with the given ISBN exists, optionally excluding a specific book ID.
     *
     * @param string $isbn The ISBN to check for.
     * @param int|null $excludeId Optional book ID to exclude from the existence check.
     * @return bool `true` if a book with the given ISBN exists, `false` otherwise.
     */
    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): bool => $this->service->existsByIsbn($isbn, $excludeId),
        );
    }
}