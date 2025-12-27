<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\authors\queries\AuthorReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\infrastructure\persistence\Author;
use yii\data\ActiveDataProvider;

/**
 * @codeCoverageIgnore Инфраструктурный репозиторий: покрыт функциональными тестами
 */
final class AuthorRepository implements AuthorRepositoryInterface
{
    public function create(string $fio): int
    {
        $author = Author::create($fio);

        if (!$author->save()) {
            $errors = $author->getFirstErrors();
            $message = $errors ? array_shift($errors) : 'Failed to create author';
            throw new \RuntimeException($message);
        }

        return $author->id;
    }

    public function update(int $id, string $fio): void
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new \RuntimeException('Author not found');
        }

        $author->edit($fio);

        if (!$author->save()) {
            $errors = $author->getFirstErrors();
            $message = $errors ? array_shift($errors) : 'Failed to save author';
            throw new \RuntimeException($message);
        }
    }

    public function findById(int $id): ?AuthorReadDto
    {
        $author = Author::findOne($id);
        if (!$author) {
            return null;
        }

        return new AuthorReadDto(
            id: $author->id,
            fio: $author->fio
        );
    }

    public function delete(int $id): void
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new \RuntimeException('Author not found');
        }

        if (!$author->delete()) {
            throw new \RuntimeException('Failed to delete author');
        }
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

    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        $query = Author::find()->where(['fio' => $fio]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }
}
