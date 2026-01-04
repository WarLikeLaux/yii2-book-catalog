<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\authors\queries\AuthorReadDto;

interface AuthorQueryServiceInterface
{
    public function findById(int $id): ?AuthorReadDto;

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array;

    public function search(string $search, int $page, int $pageSize): PagedResultInterface;

    /**
     * @param array<int> $ids
     * @return array<int>
     */
    public function findMissingIds(array $ids): array;
}
