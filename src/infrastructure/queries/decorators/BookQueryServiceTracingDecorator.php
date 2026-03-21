<?php

declare(strict_types=1);

namespace app\infrastructure\queries\decorators;

use app\application\books\queries\BookColumnFilterDto;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\SortRequest;
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

    public function search(string $term, int $page, int $limit, ?SortRequest $sort = null): PagedResultInterface
    {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->search($term, $page, $limit, $sort),
        );
    }

    public function searchPublished(string $term, int $page, int $limit, ?SortRequest $sort = null): PagedResultInterface
    {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->searchPublished($term, $page, $limit, $sort),
        );
    }

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $limit,
        ?SortRequest $sort = null,
    ): PagedResultInterface {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->searchBySpecification($specification, $page, $limit, $sort),
        );
    }

    public function searchWithFilters(
        BookColumnFilterDto $filter,
        int $page,
        int $limit,
        ?SortRequest $sort = null,
    ): PagedResultInterface {
        return $this->tracer->trace(
            'BookQuery::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->service->searchWithFilters($filter, $page, $limit, $sort),
        );
    }
}
