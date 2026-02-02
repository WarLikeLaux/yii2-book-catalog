<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\infrastructure\persistence\Author;

final readonly class AuthorRepository extends BaseActiveRecordRepository implements AuthorRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function save(AuthorEntity $author): int
    {
        $isNew = $author->getId() === null;

        $model = $isNew ? new Author() : $this->getArForEntity($author, Author::class, DomainErrorCode::AuthorNotFound);

        $model->fio = $author->fio;

        $this->persist($model, DomainErrorCode::AuthorStaleData, DomainErrorCode::AuthorFioExists);

        if ($isNew) {
            if ($model->id === null) {
                throw new OperationFailedException(DomainErrorCode::EntityPersistFailed); // @codeCoverageIgnore
            }

            $this->assignId($author, $model->id);
        }

        $this->registerIdentity($author, $model);

        return (int)$model->id;
    }

    public function get(int $id): AuthorEntity
    {
        $ar = $this->getArById($id, Author::class, DomainErrorCode::AuthorNotFound);

        $entity = new AuthorEntity((int)$ar->id, $ar->fio);
        $this->registerIdentity($entity, $ar);

        return $entity;
    }

    public function delete(AuthorEntity $author): void
    {
        $this->deleteEntity($author, Author::class, DomainErrorCode::AuthorNotFound);
    }
}
