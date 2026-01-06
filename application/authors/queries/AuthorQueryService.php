<?php

declare(strict_types=1);

namespace app\application\authors\queries;

use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;

final readonly class AuthorQueryService
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    public function getIndexProvider(int $page = 1, int $pageSize = 20): PagedResultInterface
    {
        return $this->queryService->search('', $page, $pageSize);
    }

    /**
     * @return array<int, string>
     */
    public function getAuthorsMap(): array
    {
        $authors = $this->queryService->findAllOrderedByFio();
        $map = [];

        foreach ($authors as $author) {
            $map[$author->id] = $author->fio;
        }

        return $map;
    }

    public function getById(int $id): AuthorReadDto
    {
        $author = $this->queryService->findById($id);

        if (!$author instanceof AuthorReadDto) {
            throw new EntityNotFoundException(DomainErrorCode::AuthorNotFound);
        }

        return $author;
    }

    public function search(AuthorSearchCriteria $criteria): AuthorSearchResponse
    {
        $result = $this->queryService->search(
            $criteria->search,
            $criteria->page,
            $criteria->pageSize,
        );

        /** @var AuthorReadDto[] $items */
        $items = $result->getModels();

        return new AuthorSearchResponse(
            items: $items,
            total: $result->getTotalCount(),
            page: $criteria->page,
            pageSize: $criteria->pageSize,
        );
    }

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    public function findMissingIds(array $ids): array
    {
        return $this->queryService->findMissingIds($ids);
    }
}
