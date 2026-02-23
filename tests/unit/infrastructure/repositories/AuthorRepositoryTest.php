<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\OperationFailedException;
use app\domain\repositories\AuthorRepositoryInterface;
use Codeception\Test\Unit;
use DbCleaner;
use Yii;

final class AuthorRepositoryTest extends Unit
{
    private AuthorRepositoryInterface $repository;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(AuthorRepositoryInterface::class);
        $this->cleanup();
    }

    protected function _after(): void
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        DbCleaner::clear(['authors']);
    }

    public function testSaveAndGet(): void
    {
        $author = AuthorEntity::create('Test Author');
        $this->repository->save($author);

        $this->assertNotNull($author->id);

        $fetched = $this->repository->get($author->id);
        $this->assertNotNull($fetched);
        $this->assertSame('Test Author', $fetched->fio);
    }

    public function testUpdate(): void
    {
        $author = AuthorEntity::create('Old Name');
        $this->repository->save($author);

        $author->update('New Name');
        $this->repository->save($author);

        $fetched = $this->repository->get($author->id);
        $this->assertSame('New Name', $fetched->fio);
    }

    public function testDelete(): void
    {
        $author = AuthorEntity::create('To Delete');
        $this->repository->save($author);

        $this->repository->delete($author);

        $this->expectException(EntityNotFoundException::class);
        $this->repository->get($author->id);
    }

    public function testDeleteThrowsWhenIdIsNull(): void
    {
        $author = AuthorEntity::create('Unsaved Author');

        $this->expectException(OperationFailedException::class);
        $this->repository->delete($author);
    }

    public function testGetThrowsExceptionOnNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->get(99999);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $author = AuthorEntity::reconstitute(99999, 'Non Existent');

        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete($author);
    }

    public function testSaveUpdateNonExistentAuthorThrowsException(): void
    {
        $author = AuthorEntity::reconstitute(99999, 'Non Existent');

        $this->expectException(EntityNotFoundException::class);
        $this->repository->save($author);
    }

    public function testSaveDuplicateThrowsAlreadyExistsException(): void
    {
        $author1 = AuthorEntity::create('Unique Author');
        $this->repository->save($author1);

        $author2 = AuthorEntity::create('Unique Author');

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage('author.error.fio_exists');
        $this->repository->save($author2);
    }

    public function testSaveReconstitutedEntityWithoutPriorGet(): void
    {
        $author = AuthorEntity::create('Original Name');
        $this->repository->save($author);
        $authorId = $author->id;

        $freshRepository = Yii::$container->get(AuthorRepositoryInterface::class);
        $reconstituted = AuthorEntity::reconstitute($authorId, 'Updated via Reconstitute');

        $freshRepository->save($reconstituted);

        $retrieved = $freshRepository->get($authorId);
        $this->assertSame('Updated via Reconstitute', $retrieved->fio);
    }
}
