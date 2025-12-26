<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookReadDto;
use app\domain\values\BookYear;
use app\domain\values\Isbn;

interface BookRepositoryInterface
{
    public function create(
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): int;

    public function update(
        int $id,
        string $title,
        BookYear $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): void;

    public function findById(int $id): ?BookReadDto;

    public function delete(int $id): void;

    public function syncAuthors(int $bookId, array $authorIds): void;

    public function findByIdWithAuthors(int $id): ?BookReadDto;

    public function search(string $term, int $page, int $pageSize): PagedResultInterface;

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
