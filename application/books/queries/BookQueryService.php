<?php

declare(strict_types=1);

namespace app\application\books\queries;

use app\application\ports\BookFinderInterface;
use app\application\ports\BookSearcherInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;

final readonly class BookQueryService
{
    public function __construct(
        private BookFinderInterface $finder,
        private BookSearcherInterface $searcher,
    ) {
    }

    public function getIndexProvider(int $page = 1, int $limit = 20): PagedResultInterface
    {
        return $this->searcher->search('', $page, $limit);
    }

    public function getById(int $id): BookReadDto
    {
        $book = $this->finder->findByIdWithAuthors($id);

        if (!$book instanceof BookReadDto) {
            throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
        }

        return $book;
    }

    public function search(BookSearchCriteria $criteria): PagedResultInterface
    {
        return $this->searcher->search(
            $criteria->globalSearch,
            $criteria->page,
            $criteria->limit,
        );
    }
}
