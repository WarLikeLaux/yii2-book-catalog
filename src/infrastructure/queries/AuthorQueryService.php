<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\infrastructure\persistence\Author;

final readonly class AuthorQueryService extends BaseQueryService implements AuthorQueryServiceInterface
{
    public function findById(int $id): ?AuthorReadDto
    {
        $author = Author::find()->where(['id' => $id])->one($this->db);

        if ($author === null) {
            return null;
        }

        return $this->mapToDto($author, AuthorReadDto::class);
    }

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array
    {
        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all($this->db);

        return array_map(
            fn(Author $author): AuthorReadDto => $this->mapToDto($author, AuthorReadDto::class),
            $authors,
        );
    }

    public function search(string $search, int $page, int $limit): PagedResultInterface
    {
        $query = Author::find()->orderBy(['fio' => SORT_ASC]);

        if ($search !== '') {
            $query->andWhere(['like', 'fio', $search]);
        }

        return $this->getPagedResult($query, $page, $limit, AuthorReadDto::class);
    }

    /**
     * @param array<int> $ids
     * @return array<int>
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

    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        return $this->exists(Author::find()->where(['fio' => $fio]), $excludeId);
    }

    public function existsById(int $id): bool
    {
        return Author::find()->where(['id' => $id])->exists($this->db);
    }
}
