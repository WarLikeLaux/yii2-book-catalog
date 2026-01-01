<?php

declare(strict_types=1);

namespace app\application\authors\queries;

use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainException;

final readonly class AuthorQueryService
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function getIndexProvider(int $page = 1, int $pageSize = 20): PagedResultInterface
    {
        return $this->authorRepository->search('', $page, $pageSize);
    }

    /**
     * @return array<int, string>
     */
    public function getAuthorsMap(): array
    {
        $authors = $this->authorRepository->findAllOrderedByFio();
        $map = [];
        foreach ($authors as $author) {
            $map[$author->id] = $author->fio;
        }
        return $map;
    }

    public function getById(int $id): AuthorReadDto
    {
        $author = $this->authorRepository->findById($id);
        if (!$author instanceof AuthorReadDto) {
            throw new DomainException('author.error.not_found');
        }

        return $author;
    }

    public function search(AuthorSearchCriteria $criteria): AuthorSearchResponse
    {
        $result = $this->authorRepository->search(
            $criteria->search,
            $criteria->page,
            $criteria->pageSize
        );

        /** @var AuthorReadDto[] $items */
        $items = $result->getModels();

        return new AuthorSearchResponse(
            items: $items,
            total: $result->getTotalCount(),
            page: $criteria->page,
            pageSize: $criteria->pageSize
        );
    }
}
