<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\authors\queries\AuthorReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\infrastructure\persistence\Author;
use yii\data\ActiveDataProvider;
use yii\db\Connection;

final readonly class AuthorQueryService implements AuthorQueryServiceInterface
{
    public function __construct(
        private Connection $db
    ) {
    }

    public function findById(int $id): ?AuthorReadDto
    {
        $author = Author::findOne($id);
        if ($author === null) {
            return null;
        }

        return new AuthorReadDto(
            id: $author->id,
            fio: $author->fio
        );
    }

    /**
     * @return AuthorReadDto[]
     */
    public function findAllOrderedByFio(): array
    {
        $authors = Author::find()->orderBy(['fio' => SORT_ASC])->all();
        return array_map(
            fn(Author $author): AuthorReadDto => new AuthorReadDto(
                id: $author->id,
                fio: $author->fio
            ),
            $authors
        );
    }

    public function search(string $search, int $page, int $pageSize): PagedResultInterface
    {
        $query = Author::find()->orderBy(['fio' => SORT_ASC]);

        if ($search !== '') {
            $query->andWhere(['like', 'fio', $search]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'db' => $this->db,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            fn(Author $author): AuthorReadDto => new AuthorReadDto(
                id: $author->id,
                fio: $author->fio
            ),
            $dataProvider->getModels()
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination
        );
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
            static fn(int $id): bool => !isset($existingIdsMap[$id])
        ));
    }
}
