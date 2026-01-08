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
 * Determines which IDs from the given list are not present.
 *
 * @param array<int> $ids The list of author IDs to check.
 * @return array<int> The subset of `$ids` that were not found.
 */
    public function findMissingIds(array $ids): array;

    /**
 * Checks whether an author with the given full name exists.
 *
 * If `$excludeId` is provided, the author with that ID is ignored when performing the check.
 *
 * @param string $fio The author's full name (FIO) to check.
 * @param int|null $excludeId Optional author ID to exclude from the existence check.
 * @return bool `true` if an author with the given FIO exists (excluding the specified ID), `false` otherwise.
 */
public function existsByFio(string $fio, ?int $excludeId = null): bool;
}