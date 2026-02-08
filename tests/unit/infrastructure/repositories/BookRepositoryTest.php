<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookStatus;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use Codeception\Test\Unit;
use DbCleaner;
use ReflectionProperty;
use Yii;

final class BookRepositoryTest extends Unit
{
    protected \UnitTester $tester;
    private BookRepositoryInterface $repository;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(BookRepositoryInterface::class);
        $this->cleanup();
    }

    protected function _after(): void
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors']);
    }

    public function testCreateAndFindById(): void
    {
        $book = BookEntity::create(
            'Test Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            'Desc',
            null,
        );

        $this->repository->save($book);
        $this->assertNotNull($book->id);

        $retrieved = $this->repository->get($book->id);
        $this->assertSame('Test Book', $retrieved->title);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $book = BookEntity::reconstitute(
            999,
            'Title',
            new BookYear(2025),
            new Isbn('9783161484100'),
            null,
            null,
            [],
            BookStatus::Draft,
            1,
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
            null,
        );
        $book->replaceAuthors([$authorId]);

        $this->repository->save($book);
        $bookId = $book->id;

        $retrieved = $this->repository->get($bookId);
        $this->assertContains($authorId, $retrieved->authorIds);
    }

    public function testGetReturnsBookEntity(): void
    {
        $book = BookEntity::create(
            'Get Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Description',
            null,
        );
        $this->repository->save($book);

        $retrieved = $this->repository->get($book->id);

        $this->assertSame('Get Test', $retrieved->title);
        $this->assertSame(2024, $retrieved->year->value);
    }

    public function testGetThrowsExceptionOnNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->get(99999);
    }

    public function testUpdateExistingBook(): void
    {
        $book = BookEntity::create(
            'Original Title',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);

        $updated = $this->repository->get($book->id);
        $updated->rename('Updated Title');
        $updated->changeYear(new BookYear(2025));
        $updated->correctIsbn(new Isbn('9783161484100'));
        $updated->updateDescription('New desc');
        $this->repository->save($updated);

        $retrieved = $this->repository->get($book->id);
        $this->assertSame('Updated Title', $retrieved->title);
        $this->assertSame(2025, $retrieved->year->value);
    }

    public function testDeleteSuccessfully(): void
    {
        $book = BookEntity::create(
            'To Delete',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);
        $bookId = $book->id;

        $this->repository->delete($book);

        $this->expectException(EntityNotFoundException::class);
        $this->repository->get($bookId);
    }

    public function testUpdateNonExistentBookThrowsException(): void
    {
        $book = BookEntity::create(
            'Non-existent',
            new BookYear(2023),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
        );
        $this->assignBookId($book, 99999);

        $this->expectException(EntityNotFoundException::class);
        $this->repository->save($book);
    }

    public function testUpdateBookRemovesAuthor(): void
    {
        $author1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author One']);
        $author2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author Two']);

        $book = BookEntity::create(
            'Book with Authors',
            new BookYear(2023),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
        );
        $book->replaceAuthors([$author1, $author2]);
        $this->repository->save($book);

        $book->replaceAuthors([$author1]);
        $this->repository->save($book);

        $storedBook = $this->repository->get($book->id);
        $this->assertCount(1, $storedBook->authorIds);
        $this->assertEquals([$author1], $storedBook->authorIds);
    }

    public function testSaveDuplicateIsbnThrowsAlreadyExistsException(): void
    {
        $isbn = '9783161484100';
        $book1 = BookEntity::create(
            'First Book',
            new BookYear(2024),
            new Isbn($isbn),
            null,
            null,
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Duplicate ISBN Book',
            new BookYear(2025),
            new Isbn($isbn),
            null,
            null,
        );

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage('book.error.isbn_exists');
        $this->repository->save($book2);
    }

    public function testSaveReconstitutedEntityWithoutPriorGet(): void
    {
        $book = BookEntity::create(
            'Original',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);
        $bookId = $book->id;
        $version = $book->version;

        $freshRepository = Yii::$container->get(BookRepositoryInterface::class);
        $reconstituted = BookEntity::reconstitute(
            $bookId,
            'Reconstituted Title',
            new BookYear(2025),
            new Isbn('9783161484100'),
            'New desc',
            null,
            [],
            BookStatus::Draft,
            $version,
        );

        $freshRepository->save($reconstituted);

        $retrieved = $freshRepository->get($bookId);
        $this->assertSame('Reconstituted Title', $retrieved->title);
    }

    private function assignBookId(BookEntity $book, int $id): void
    {
        $property = new ReflectionProperty(BookEntity::class, 'id');
        $property->setValue($book, $id);
    }
}
