<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\authors\queries\AuthorReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\infrastructure\persistence\Author;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;

final readonly class AuthorRepository implements AuthorRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;

    public function save(AuthorEntity $author): void
    {
        if ($author->getId() === null) {
            $ar = Author::create($author->getFio());
        } else {
            $ar = Author::findOne($author->getId());
            if ($ar === null) {
                throw new EntityNotFoundException('author.error.not_found');
            }
            $ar->edit($author->getFio());
        }

        if ($this->existsByFio($author->getFio(), $author->getId())) {
            throw new AlreadyExistsException('author.error.fio_exists', 409);
        }

        $this->persistAuthor($ar);

        if ($author->getId() !== null) {
            return;
        }

        $author->setId($ar->id);
    }

    public function get(int $id): AuthorEntity
    {
        $ar = Author::findOne($id);
        if ($ar === null) {
            throw new EntityNotFoundException('author.error.not_found');
        }

        return new AuthorEntity(
            $ar->id,
            $ar->fio
        );
    }

    public function delete(AuthorEntity $author): void
    {
        $ar = Author::findOne($author->getId());
        if ($ar === null) {
            throw new EntityNotFoundException('author.error.not_found');
        }

        if ($ar->delete() === false) {
            throw new \RuntimeException('author.error.delete_failed'); // @codeCoverageIgnore
        }
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

    /** @codeCoverageIgnore Защитный код (недостижим из-за валидации домена) */
    private function persistAuthor(Author $ar): void
    {
        try {
            if (!$ar->save()) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : 'author.error.save_failed';
                throw new \RuntimeException($message);
            }
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException('author.error.fio_exists', 409, $e);
            }
            throw $e;
        }
    }
}
