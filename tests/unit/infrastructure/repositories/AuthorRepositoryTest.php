<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\infrastructure\persistence\Author;
use app\infrastructure\repositories\AuthorRepository;
use Codeception\Test\Unit;

final class AuthorRepositoryTest extends Unit
{
    protected \UnitTester $tester;
    private AuthorRepository $repository;

    protected function _before(): void
    {
        $this->repository = new AuthorRepository();
        Author::deleteAll();
    }

    public function testCreateAndFindById(): void
    {
        $id = $this->repository->create('Test Author');
        $dto = $this->repository->findById($id);

        $this->assertNotNull($dto);
        $this->assertSame('Test Author', $dto->fio);
    }

    public function testUpdate(): void
    {
        $id = $this->repository->create('Old Name');
        $this->repository->update($id, 'New Name');

        $dto = $this->repository->findById($id);
        $this->assertSame('New Name', $dto->fio);
    }

    public function testDelete(): void
    {
        $id = $this->repository->create('To Delete');
        $this->repository->delete($id);

        $this->assertNull($this->repository->findById($id));
    }

    public function testExistsByFio(): void
    {
        $this->repository->create('Existing');
        
        $this->assertTrue($this->repository->existsByFio('Existing'));
        $this->assertFalse($this->repository->existsByFio('Non Existent'));
    }
}
