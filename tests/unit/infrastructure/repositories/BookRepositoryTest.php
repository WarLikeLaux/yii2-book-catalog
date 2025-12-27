<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

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
        $id = $this->repository->create(
            'Test Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            'Desc',
            null
        );

        $dto = $this->repository->findById($id);
        $this->assertNotNull($dto);
        $this->assertSame('Test Book', $dto->title);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->delete(999);
    }

    public function testSyncAuthors(): void
    {
        $bookId = $this->repository->create(
            'Book', new BookYear(2025), new Isbn('9783161484100'), null, null
        );
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Author']);

        $this->repository->syncAuthors($bookId, [$authorId]);
        
        $dto = $this->repository->findByIdWithAuthors($bookId);
        $this->assertContains($authorId, $dto->authorIds);
    }
}
