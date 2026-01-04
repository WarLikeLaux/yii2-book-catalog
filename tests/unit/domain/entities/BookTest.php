<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;

final class BookTest extends Unit
{
    private function createBook(
        string $title = 'Title',
        int $year = 2023,
        string $isbn = '978-3-16-148410-0',
    ): Book {
        return Book::create(
            $title,
            new BookYear($year, new \DateTimeImmutable()),
            new Isbn($isbn),
            null,
            null,
        );
    }

    private function createBookWithAuthors(int ...$authorIds): Book
    {
        $book = $this->createBook();
        if ($authorIds) {
            $book->replaceAuthors($authorIds);
        }
        return $book;
    }

    public function testCreate(): void
    {
        $book = $this->createBook();

        $this->assertNull($book->id);
        $this->assertSame('Title', $book->title);
        $this->assertFalse($book->published);
        $this->assertSame(1, $book->version);
        $this->assertSame([], $book->authorIds);
    }

    public function testUpdate(): void
    {
        $book = Book::reconstitute(
            1,
            'Old Title',
            new BookYear(2020, new \DateTimeImmutable()),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
            [],
            false,
            1
        );

        $newYear = new BookYear(2024, new \DateTimeImmutable());
        $newIsbn = new Isbn('978-3-16-148410-0');
        $newCover = new StoredFileReference('path/to/new');

        $book->rename('New Title');
        $book->changeYear($newYear);
        $book->correctIsbn($newIsbn);
        $book->updateDescription('New Desc');
        $book->updateCover($newCover);

        $this->assertSame('New Title', $book->title);
        $this->assertSame($newYear, $book->year);
        $this->assertSame('New Desc', $book->description);
        $this->assertSame($newCover, $book->coverImage);

        $book->updateCover(null);
        $this->assertNull($book->coverImage, 'Cover image should be null if removed');
    }

    public function testAddAndRemoveAuthors(): void
    {
        $book = $this->createBook();
        $book->addAuthor(1);
        $book->addAuthor(2);

        $this->assertSame([1, 2], $book->authorIds);
        $this->assertTrue($book->hasAuthor(1));
        $this->assertFalse($book->hasAuthor(2) && $book->hasAuthor(3));

        $book->removeAuthor(1);
        $this->assertSame([2], $book->authorIds);
    }

    public function testAddAuthorIsIdempotent(): void
    {
        $book = $this->createBook();
        $book->addAuthor(1);
        $book->addAuthor(1);

        $this->assertSame([1], $book->authorIds);
    }

    public function testRemoveAuthorIsIdempotent(): void
    {
        $book = $this->createBookWithAuthors(1, 2);
        $book->removeAuthor(3);

        $this->assertSame([1, 2], $book->authorIds);
    }

    public function testAddAuthorThrowsExceptionOnInvalidId(): void
    {
        $book = $this->createBook();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_author_id');

        $book->addAuthor(0);
    }

    public function testReplaceAuthors(): void
    {
        $book = $this->createBook();
        $book->replaceAuthors([1, 2, 3]);

        $this->assertSame([1, 2, 3], $book->authorIds);
    }

    public function testIncrementVersion(): void
    {
        $book = Book::reconstitute(
            1,
            'Title',
            new BookYear(2023, new \DateTimeImmutable()),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
            [],
            false,
            5
        );

        $book->incrementVersion();

        $this->assertSame(6, $book->version);
    }

    public function testPublishRequiresAuthors(): void
    {
        $book = $this->createBook();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $book->publish(new BookPublicationPolicy());
    }

    public function testPublishWithAuthorsSucceeds(): void
    {
        $book = $this->createBookWithAuthors(1, 2);
        $book->publish(new BookPublicationPolicy());

        $this->assertTrue($book->published);
    }

    public function testChangeIsbnOnDraftBookSucceeds(): void
    {
        $book = $this->createBook();
        $newIsbn = new Isbn('979-10-90636-07-1');

        $book->correctIsbn($newIsbn);

        $this->assertTrue($book->isbn->equals($newIsbn));
    }

    public function testChangeIsbnOnPublishedBookThrows(): void
    {
        $book = $this->createBookWithAuthors(1);
        $book->publish(new BookPublicationPolicy());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.isbn_change_published');

        $book->correctIsbn(new Isbn('979-10-90636-07-1'));
    }

    public function testSameIsbnOnPublishedBookSucceeds(): void
    {
        $book = $this->createBookWithAuthors(1);
        $book->publish(new BookPublicationPolicy());
        $book->rename('New Title');

        $this->assertSame('New Title', $book->title);
    }

    public function testCreateThrowsOnInvalidTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        Book::create('', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testCreateThrowsOnTooLongTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        Book::create(str_repeat('A', 256), new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testRenameThrowsOnInvalidTitle(): void
    {
        $book = $this->createBook('Valid Title');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        $book->rename('');
    }

    public function testRenameThrowsOnTooLongTitle(): void
    {
        $book = $this->createBook('Valid Title');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        $book->rename(str_repeat('X', 256));
    }

    private function assignBookId(Book $book, int $id): void
    {
        $property = new \ReflectionProperty(Book::class, 'id');
        $property->setValue($book, $id);
    }
}
