<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\services;

use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author as AuthorEntity;
use app\infrastructure\persistence\Author;
use Codeception\Test\Unit;
use Yii;

final class AuthorExistenceCheckerTest extends Unit
{
    private AuthorExistenceCheckerInterface $checker;
    private AuthorRepositoryInterface $repository;

    protected function _before(): void
    {
        $this->checker = Yii::$container->get(AuthorExistenceCheckerInterface::class);
        $this->repository = Yii::$container->get(AuthorRepositoryInterface::class);
        Author::deleteAll();
    }

    public function testExistsByFioReturnsTrue(): void
    {
        $author = AuthorEntity::create('Unique FIO');
        $this->repository->save($author);

        $this->assertTrue($this->checker->existsByFio('Unique FIO'));
    }

    public function testExistsByFioReturnsFalse(): void
    {
        $this->assertFalse($this->checker->existsByFio('Nonexistent FIO'));
    }

    public function testExistsByFioWithExcludeId(): void
    {
        $author = AuthorEntity::create('Exclude FIO');
        $this->repository->save($author);

        $this->assertFalse($this->checker->existsByFio('Exclude FIO', $author->id));
        $this->assertTrue($this->checker->existsByFio('Exclude FIO', 99999));
    }

    public function testExistsByIdReturnsTrue(): void
    {
        $author = AuthorEntity::create('Test Author');
        $this->repository->save($author);

        $this->assertTrue($this->checker->existsById($author->id));
    }

    public function testExistsByIdReturnsFalse(): void
    {
        $this->assertFalse($this->checker->existsById(99999));
    }

    public function testExistsAllByIdsWithEmptyArray(): void
    {
        $this->assertTrue($this->checker->existsAllByIds([]));
    }

    public function testExistsAllByIdsAllExist(): void
    {
        $author1 = AuthorEntity::create('Author 1');
        $author2 = AuthorEntity::create('Author 2');
        $this->repository->save($author1);
        $this->repository->save($author2);

        $this->assertTrue($this->checker->existsAllByIds([$author1->id, $author2->id]));
    }

    public function testExistsAllByIdsSomeMissing(): void
    {
        $author = AuthorEntity::create('Existing Author');
        $this->repository->save($author);

        $this->assertFalse($this->checker->existsAllByIds([$author->id, 99998, 99999]));
    }

    public function testExistsAllByIdsAllMissing(): void
    {
        $this->assertFalse($this->checker->existsAllByIds([99998, 99999]));
    }

    public function testExistsAllByIdsWithDuplicatesReturnsTrueWhenAllExist(): void
    {
        $author1 = AuthorEntity::create('Author 1');
        $author2 = AuthorEntity::create('Author 2');
        $this->repository->save($author1);
        $this->repository->save($author2);

        $this->assertTrue($this->checker->existsAllByIds([$author1->id, $author2->id, $author1->id]));
    }

    public function testExistsAllByIdsWithDuplicatesReturnsFalseWhenOneMissing(): void
    {
        $author = AuthorEntity::create('Existing Author');
        $this->repository->save($author);

        $this->assertFalse($this->checker->existsAllByIds([$author->id, $author->id, 99999]));
    }
}
