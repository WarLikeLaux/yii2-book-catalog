<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\DomainErrorCode;
use app\infrastructure\persistence\Author;

final readonly class AuthorRepository extends BaseActiveRecordRepository implements AuthorRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function save(AuthorEntity $author): void
    {
        if ($author->id === null) {
            $ar = new Author();
        } else {
            $ar = $this->getArForEntity($author, Author::class, DomainErrorCode::AuthorNotFound);
        }

        $ar->fio = $author->fio;

        $this->persist($ar, DomainErrorCode::AuthorStaleData, DomainErrorCode::AuthorFioExists);

        if ($author->id !== null) {
            return;
        }

        $this->assignId($author, $ar->id); // @phpstan-ignore property.notFound
        $this->registerIdentity($author, $ar);
    }

    public function get(int $id): AuthorEntity
    {
        $ar = $this->getArById($id, Author::class, DomainErrorCode::AuthorNotFound);

        $entity = new AuthorEntity($ar->id, $ar->fio);
        $this->registerIdentity($entity, $ar);

        return $entity;
    }

    public function delete(AuthorEntity $author): void
    {
        $this->deleteEntity($author, Author::class, DomainErrorCode::AuthorNotFound);
    }
}
