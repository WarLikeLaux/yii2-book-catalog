<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookReadDto;
use app\domain\entities\Book;

interface BookRepositoryInterface
{
    public function save(Book $book): void;

    public function get(int $id): Book;

    /**
     * @return BookReadDto|null Read-model access
     */
    public function findById(int $id): ?BookReadDto;

    public function delete(Book $book): void;

    public function findByIdWithAuthors(int $id): ?BookReadDto;

    public function search(string $term, int $page, int $pageSize): PagedResultInterface;

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
