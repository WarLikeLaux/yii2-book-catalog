<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\infrastructure\persistence\Author;

final readonly class AuthorQueryService extends BaseQueryService implements AuthorQueryServiceInterface
{
    /**
     * Retrieve an author by ID and return its read DTO.
     *
     * @param int $id The author identifier to look up.
     * @return AuthorReadDto|null The mapped AuthorReadDto if an author with the given ID exists, `null` otherwise.
     */
    public function findById(int $id): ?AuthorReadDto
    {
        $author = Author::find()->where(['id' => $id])->one($this->db);

        if ($author === null) {
            return null;
        }

        return $this->mapToDto($author, AuthorReadDto::class);
    }

    /**
     * Retrieve all authors ordered by the `fio` field in ascending order.
     *
     * @return AuthorReadDto[] An indexed array of AuthorReadDto objects ordered by `fio` (ascending).
     */
    public function findAllOrderedByFio(): array
    {
        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all($this->db);

        return array_map(
            fn(Author $author): AuthorReadDto => $this->mapToDto($author, AuthorReadDto::class),
            $authors,
        );
    }

    /**
     * Searches authors by their `fio` field and returns a paged result.
     *
     * If `$search` is an empty string, all authors are returned ordered by `fio` ascending.
     *
     * @param string $search Substring to match against the `fio` field.
     * @param int $page Page number to return.
     * @param int $pageSize Number of items per page.
     * @return PagedResultInterface A paged result containing `AuthorReadDto` objects for the requested page.
     */
    public function search(string $search, int $page, int $pageSize): PagedResultInterface
    {
        $query = Author::find()->orderBy(['fio' => SORT_ASC]);

        if ($search !== '') {
            $query->andWhere(['like', 'fio', $search]);
        }

        return $this->getPagedResult($query, $page, $pageSize, AuthorReadDto::class);
    }

    /**
     * Returns the subset of given author IDs that do not exist in the persistence layer.
     *
     * @param array<int> $ids List of author IDs to check.
     * @return array<int> Indexed list of IDs from `$ids` that were not found.
     */
    public function findMissingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $existingIds = Author::find()
            ->select('id')
            ->where(['id' => $ids])
            ->column($this->db);

        $existingIdsMap = array_flip($existingIds);

        return array_values(array_filter(
            $ids,
            static fn(int $id): bool => !isset($existingIdsMap[$id]),
        ));
    }

    /**
     * Check if an author with the given fio exists.
     *
     * @param string $fio The fio value to check for.
     * @param int|null $excludeId Optional author ID to exclude from the check.
     * @return bool `true` if an author with the given fio exists, `false` otherwise.
     */
    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        return $this->exists(Author::find()->where(['fio' => $fio]), $excludeId);
    }
}