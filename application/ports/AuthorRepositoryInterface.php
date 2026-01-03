<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\authors\queries\AuthorReadDto;
use app\domain\entities\Author;

interface AuthorRepositoryInterface
{
    public function save(Author $author): void;

    public function get(int $id): Author;

    public function findById(int $id): ?AuthorReadDto;

    public function delete(Author $author): void;

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array;

    public function search(string $search, int $page, int $pageSize): PagedResultInterface;

    public function existsByFio(string $fio, ?int $excludeId = null): bool;

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    public function findMissingIds(array $ids): array;
}
