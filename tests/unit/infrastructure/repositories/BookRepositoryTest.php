<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\repositories\BookRepository;
use Codeception\Test\Unit;

final class BookRepositoryTest extends Unit
{
    protected \UnitTester $tester;
    private BookRepository $repository;

    protected function _before(): void
    {
        $this->repository = new BookRepository();
        Book::deleteAll();
        Author::deleteAll();
    }

    public function testCreateAndFindById(): void
    {
        $book = BookEntity::create(
            'Test Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            'Desc',
            null
        );

        $this->repository->save($book);
        $this->assertNotNull($book->getId());

        $dto = $this->repository->findById($book->getId());
        $this->assertNotNull($dto);
        $this->assertSame('Test Book', $dto->title);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $book = new BookEntity(
            999,
            'Title',
            new BookYear(2025),
            new Isbn('9783161484100'),
            null,
            null
        );

        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete($book);
    }

    public function testSyncAuthors(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Author']);

        $book = BookEntity::create(
            'Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->syncAuthors([$authorId]);

        $this->repository->save($book);
        $bookId = $book->getId();
        
        $dto = $this->repository->findByIdWithAuthors($bookId);
        $this->assertContains($authorId, $dto->authorIds);
    }
}
