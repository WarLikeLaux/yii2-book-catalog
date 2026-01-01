<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\infrastructure\persistence\Author;
use Codeception\Test\Unit;
use Yii;

final class AuthorRepositoryTest extends Unit
{
    private AuthorRepositoryInterface $repository;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(AuthorRepositoryInterface::class);
        Author::deleteAll();
    }

    public function testCreateAndFindById(): void
    {
        $author = AuthorEntity::create('Test Author');
        $this->repository->save($author);

        $this->assertNotNull($author->getId());

        $fetched = $this->repository->get($author->getId());
        $this->assertNotNull($fetched);
        $this->assertSame('Test Author', $fetched->getFio());

        $dto = $this->repository->findById($author->getId());
        $this->assertNotNull($dto);
        $this->assertSame('Test Author', $dto->fio);
    }

    public function testUpdate(): void
    {
        $author = AuthorEntity::create('Old Name');
        $this->repository->save($author);

        $author->update('New Name');
        $this->repository->save($author);

        $fetched = $this->repository->get($author->getId());
        $this->assertSame('New Name', $fetched->getFio());
    }

    public function testDelete(): void
    {
        $author = AuthorEntity::create('To Delete');
        $this->repository->save($author);

        $this->repository->delete($author);

        $this->expectException(EntityNotFoundException::class);
        $this->repository->get($author->getId());
    }

    public function testExistsByFio(): void
    {
        $author = AuthorEntity::create('Existing');
        $this->repository->save($author);

        $this->assertTrue($this->repository->existsByFio('Existing'));
        $this->assertFalse($this->repository->existsByFio('Non Existent'));
    }

    public function testExistsByFioWithExcludeId(): void
    {
        $author = AuthorEntity::create('Unique Author');
        $this->repository->save($author);

        $this->assertFalse($this->repository->existsByFio('Unique Author', $author->getId()));
        $this->assertTrue($this->repository->existsByFio('Unique Author', 99999));
    }

    public function testGetThrowsExceptionOnNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->get(99999);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $author = new AuthorEntity(99999, 'Non Existent');

        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete($author);
    }

    public function testFindAllOrderedByFio(): void
    {
        $author1 = AuthorEntity::create('Zebra Author');
        $author2 = AuthorEntity::create('Alpha Author');
        $author3 = AuthorEntity::create('Middle Author');

        $this->repository->save($author1);
        $this->repository->save($author2);
        $this->repository->save($author3);

        $result = $this->repository->findAllOrderedByFio();

        $this->assertCount(3, $result);
        $this->assertSame('Alpha Author', $result[0]->fio);
        $this->assertSame('Middle Author', $result[1]->fio);
        $this->assertSame('Zebra Author', $result[2]->fio);
    }

    public function testSearchWithEmptyTerm(): void
    {
        $author = AuthorEntity::create('Searchable Author');
        $this->repository->save($author);

        $result = $this->repository->search('', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchWithTerm(): void
    {
        $author1 = AuthorEntity::create('John Doe');
        $author2 = AuthorEntity::create('Jane Smith');
        $this->repository->save($author1);
        $this->repository->save($author2);

        $result = $this->repository->search('John', 1, 10);

        $this->assertSame(1, $result->getTotalCount());
    }

    public function testSearchPagination(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $author = AuthorEntity::create("Author $i");
            $this->repository->save($author);
        }

        $result = $this->repository->search('', 1, 10);

        $this->assertSame(15, $result->getTotalCount());
        $this->assertCount(10, $result->getModels());
        $this->assertSame(2, $result->getPagination()->totalPages);
    }

    public function testFindByIdReturnsNullOnNotFound(): void
    {
        $this->assertNull($this->repository->findById(99999));
    }

    public function testSaveUpdateNonExistentAuthorThrowsException(): void
    {
        $author = new AuthorEntity(99999, 'Non Existent');

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
}
