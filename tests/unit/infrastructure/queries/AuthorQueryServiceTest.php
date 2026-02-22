<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\application\ports\AuthorQueryServiceInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\repositories\AuthorRepositoryInterface;
use app\infrastructure\persistence\Author;
use Codeception\Test\Unit;
use Yii;

final class AuthorQueryServiceTest extends Unit
{
    private AuthorQueryServiceInterface $queryService;
    private AuthorRepositoryInterface $repository;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->queryService = Yii::$container->get(AuthorQueryServiceInterface::class);
        $this->repository = Yii::$container->get(AuthorRepositoryInterface::class);
        Author::deleteAll();
    }

    public function testFindById(): void
    {
        $author = AuthorEntity::create('Test Author');
        $this->repository->save($author);

        $dto = $this->queryService->findById($author->id);
        $this->assertNotNull($dto);
        $this->assertSame('Test Author', $dto->fio);
    }

    public function testFindAllOrderedByFio(): void
    {
        $author1 = AuthorEntity::create('Zebra Author');
        $author2 = AuthorEntity::create('Alpha Author');
        $author3 = AuthorEntity::create('Middle Author');

        $this->repository->save($author1);
        $this->repository->save($author2);
        $this->repository->save($author3);

        $result = $this->queryService->findAllOrderedByFio();

        $this->assertCount(3, $result);
        $this->assertSame('Alpha Author', $result[0]->fio);
        $this->assertSame('Middle Author', $result[1]->fio);
        $this->assertSame('Zebra Author', $result[2]->fio);
    }

    public function testSearchWithEmptyTerm(): void
    {
        $author = AuthorEntity::create('Searchable Author');
        $this->repository->save($author);

        $result = $this->queryService->search('', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchWithTerm(): void
    {
        $author1 = AuthorEntity::create('John Doe');
        $author2 = AuthorEntity::create('Jane Smith');
        $this->repository->save($author1);
        $this->repository->save($author2);

        $result = $this->queryService->search('John', 1, 10);

        $this->assertSame(1, $result->getTotalCount());
    }

    public function testSearchPagination(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $author = AuthorEntity::create("Author $i");
            $this->repository->save($author);
        }

        $result = $this->queryService->search('', 1, 10);

        $this->assertSame(15, $result->getTotalCount());
        $this->assertCount(10, $result->getModels());
        $this->assertSame(2, $result->getPagination()->totalPages);
    }

    public function testFindByIdReturnsNullOnNotFound(): void
    {
        $this->assertNull($this->queryService->findById(99999));
    }
}
