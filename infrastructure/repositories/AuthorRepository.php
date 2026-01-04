<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\infrastructure\persistence\Author;
use RuntimeException;
use yii\db\IntegrityException;

final readonly class AuthorRepository implements AuthorRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;

    public function save(AuthorEntity $author): void
    {
        if ($author->id === null) {
            $ar = new Author();
        } else {
            $ar = Author::findOne($author->id);
            if ($ar === null) {
                throw new EntityNotFoundException('author.error.not_found');
            }
        }

        $ar->fio = $author->fio;

        if ($this->existsByFio($author->fio, $author->id)) {
            throw new AlreadyExistsException('author.error.fio_exists', 409);
        }

        $this->persistAuthor($ar);

        if ($author->id !== null) {
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
        $ar = Author::findOne($author->id);
        if ($ar === null) {
            throw new EntityNotFoundException('author.error.not_found');
        }

        if ($ar->delete() === false) {
            throw new RuntimeException('author.error.delete_failed'); // @codeCoverageIgnore
        }
    }

    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        $query = Author::find()->where(['fio' => $fio]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    /** @codeCoverageIgnore */
    private function persistAuthor(Author $ar): void
    {
        try {
            if (!$ar->save(false)) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : 'author.error.save_failed';
                throw new RuntimeException($message);
            }
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException('author.error.fio_exists', 409, $e);
            }
            throw $e;
        }
    }
}
