<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\authors\queries\AuthorReadDto;

interface AuthorRepositoryInterface
{
    public function create(string $fio): int;

    public function update(int $id, string $fio): void;

    public function findById(int $id): ?AuthorReadDto;

    public function delete(int $id): void;

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array;

    public function search(string $search, int $page, int $pageSize): PagedResultInterface;

    public function existsByFio(string $fio, ?int $excludeId = null): bool;
}
