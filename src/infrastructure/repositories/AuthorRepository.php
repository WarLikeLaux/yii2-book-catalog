<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\domain\repositories\AuthorRepositoryInterface;
use app\infrastructure\components\hydrator\ActiveRecordHydrator;
use app\infrastructure\persistence\Author;
use yii\db\Connection;

final readonly class AuthorRepository extends BaseActiveRecordRepository implements AuthorRepositoryInterface
{
    use IdentityAssignmentTrait;

    public function __construct(
        private Connection $db,
        private ActiveRecordHydrator $hydrator,
    ) {
        parent::__construct();
    }

    public function save(AuthorEntity $author): int
    {
        $isNew = $author->getId() === null;

        $model = $isNew ? new Author() : $this->getArForEntity($author, Author::class, DomainErrorCode::AuthorNotFound);

        $this->hydrator->hydrate($model, $author, ['fio']);

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

        $entity = AuthorEntity::reconstitute((int)$ar->id, $ar->fio);
        $this->registerIdentity($entity, $ar);

        return $entity;
    }

    public function delete(AuthorEntity $author): void
    {
        $this->db->transaction(function () use ($author): void {
            $this->removeAllBookLinks($author->getId());
            $this->deleteEntity($author, Author::class, DomainErrorCode::AuthorNotFound);
        });
    }

    public function removeAllBookLinks(int $authorId): void
    {
        $this->db->createCommand()
            ->delete('book_authors', ['author_id' => $authorId])
            ->execute();
    }
}
