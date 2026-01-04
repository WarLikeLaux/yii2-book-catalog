<?php

declare(strict_types=1);

namespace app\application\books\queries;

use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainException;

final readonly class BookQueryService
{
    public function __construct(
        private BookQueryServiceInterface $bookRepository
    ) {
    }

    public function getIndexProvider(int $page = 1, int $pageSize = 20): PagedResultInterface
    {
        return $this->bookRepository->search('', $page, $pageSize);
    }

    public function getById(int $id): BookReadDto
    {
        $book = $this->bookRepository->findByIdWithAuthors($id);

        if (!$book instanceof BookReadDto) {
            throw new DomainException('book.error.not_found');
        }

        return $book;
    }

    public function search(BookSearchCriteria $criteria): PagedResultInterface
    {
        return $this->bookRepository->search(
            $criteria->globalSearch,
            $criteria->page,
            $criteria->pageSize
        );
    }
}
