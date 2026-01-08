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

    /**
     * Persist an AuthorEntity to storage, creating a new record when the entity has no id or updating an existing one.
     *
     * When creating, assigns the generated persistence id to the domain entity and registers the identity mapping.
     *
     * @param AuthorEntity $author The domain author to save; if $author->id is null a new persistence record is created, otherwise the existing record is updated.
     *
     * Note: save operations use DomainErrorCode::AuthorFioExists for save conflicts and DomainErrorCode::AuthorNotFound when an update target is missing.
     */
    public function save(AuthorEntity $author): void
    {
        $ar = $author->id === null ? new Author() : $this->getArForEntity($author, Author::class, DomainErrorCode::AuthorNotFound);

        $ar->fio = $author->fio;

        $this->persist($ar, DomainErrorCode::AuthorFioExists, 'author.error.save_failed');

        if ($author->id !== null) {
            return;
        }

        $this->assignId($author, $ar->id); // @phpstan-ignore property.notFound
        $this->registerIdentity($author, $ar);
    }

    /**
     * Retrieve an AuthorEntity by its identifier and register its identity mapping.
     *
     * @param int $id The identifier of the author.
     * @return AuthorEntity The domain AuthorEntity retrieved from persistence.
     */
    public function get(int $id): AuthorEntity
    {
        $ar = $this->getArById($id, Author::class, DomainErrorCode::AuthorNotFound);

        $entity = new AuthorEntity($ar->id, $ar->fio);
        $this->registerIdentity($entity, $ar);

        return $entity;
    }

    /**
     * Remove the given AuthorEntity from persistent storage.
     *
     * Signals DomainErrorCode::AuthorNotFound if the underlying record cannot be found
     * and uses the error key 'author.error.delete_failed' on deletion failure.
     *
     * @param \app\domain\entities\Author $author The domain author to delete.
     */
    public function delete(AuthorEntity $author): void
    {
        $this->deleteEntity($author, Author::class, DomainErrorCode::AuthorNotFound, 'author.error.delete_failed');
    }
}