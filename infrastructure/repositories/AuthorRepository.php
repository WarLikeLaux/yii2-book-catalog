<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\infrastructure\persistence\Author;
use RuntimeException;
use yii\db\IntegrityException;

final readonly class AuthorRepository implements AuthorRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;
    use IdentityAssignmentTrait;

    public function save(AuthorEntity $author): void
    {
        if ($author->id === null) {
            $ar = new Author();
        } else {
            $ar = Author::findOne($author->id);

            if ($ar === null) {
                throw new EntityNotFoundException(DomainErrorCode::AuthorNotFound);
            }
        }

        $ar->fio = $author->fio;

        $this->persistAuthor($ar);

        if ($author->id !== null) {
            return;
        }

        $this->assignId($author, $ar->id);
    }

    public function get(int $id): AuthorEntity
    {
        $ar = Author::findOne($id);

        if ($ar === null) {
            throw new EntityNotFoundException(DomainErrorCode::AuthorNotFound);
        }

        return new AuthorEntity(
            $ar->id,
            $ar->fio,
        );
    }

    public function delete(AuthorEntity $author): void
    {
        $ar = Author::findOne($author->id);

        if ($ar === null) {
            throw new EntityNotFoundException(DomainErrorCode::AuthorNotFound);
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
                throw new AlreadyExistsException(DomainErrorCode::AuthorFioExists, 409, $e);
            }

            throw $e;
        }
    }
}
