<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookReadDto;

interface BookRepositoryInterface
{
    public function create(
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): int;

    public function update(
        int $id,
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): void;

    public function findById(int $id): ?BookReadDto;

    public function delete(int $id): void;

    public function syncAuthors(int $bookId, array $authorIds): void;

    public function findByIdWithAuthors(int $id): ?BookReadDto;

    public function search(string $term, int $pageSize): PagedResultInterface;

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool;
}
