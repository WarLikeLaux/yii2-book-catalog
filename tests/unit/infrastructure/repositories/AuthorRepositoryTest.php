<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\TranslatorInterface;
use app\domain\entities\Author as AuthorEntity;
use app\domain\exceptions\EntityNotFoundException;
use app\infrastructure\persistence\Author;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;

final class AuthorRepositoryTest extends Unit
{
    private AuthorRepositoryInterface $repository;

    private TranslatorInterface&MockObject $translator;

    protected function _before(): void
    {
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
}
