<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class BookTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $year = new BookYear(2023, new \DateTimeImmutable());
        $isbn = new Isbn('978-3-16-148410-0');

        $book = Book::create('Title', $year, $isbn, 'Desc', 'http://url.com');

        $this->assertNull($book->id);
        $this->assertSame('Title', $book->title);
        $this->assertSame($year, $book->year);
        $this->assertSame($isbn, $book->isbn);
        $this->assertSame('Desc', $book->description);
        $this->assertSame('http://url.com', $book->coverUrl);
        $this->assertSame([], $book->authorIds);
        $this->assertSame(1, $book->version);
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

        $book->rename('New Title');
        $book->changeYear($newYear);
        $book->correctIsbn($newIsbn);
        $book->updateDescription('New Desc');
        $book->updateCover('http://new.com');

        $this->assertSame('New Title', $book->title);
        $this->assertSame($newYear, $book->year);
        $this->assertSame('New Desc', $book->description);
        $this->assertSame('http://new.com', $book->coverUrl);

        $book->updateCover(null);
        $this->assertNull($book->coverUrl, 'Cover URL should be null if removed');
    }

    public function testReplaceAuthors(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $book->replaceAuthors([1, 2, 3]);

        $this->assertSame([1, 2, 3], $book->authorIds);
    }

    public function testAddAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $book->addAuthor(1);
        $book->addAuthor(2);

        $this->assertSame([1, 2], $book->authorIds);
    }

    public function testAddAuthorIsIdempotent(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $book->addAuthor(1);
        $book->addAuthor(1);

        $this->assertSame([1], $book->authorIds);
    }

    public function testAddAuthorThrowsExceptionOnInvalidId(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_author_id');

        $book->addAuthor(0);
    }

    public function testRemoveAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2, 3]);

        $book->removeAuthor(2);

        $this->assertSame([1, 3], $book->authorIds);
    }

    public function testRemoveAuthorIsIdempotent(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2]);

        $book->removeAuthor(3);

        $this->assertSame([1, 2], $book->authorIds);
    }

    public function testHasAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $book->addAuthor(1);

        $this->assertTrue($book->hasAuthor(1));
        $this->assertFalse($book->hasAuthor(2));
    }

    public function testSetId(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->assignBookId($book, 100);
        $this->assertSame(100, $book->id);

        $this->assignBookId($book, 100);

        $this->expectException(\RuntimeException::class);
        $this->assignBookId($book, 200);
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

    public function testIsPublishedReturnsFalseByDefault(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->assertFalse($book->published);
    }

    public function testDefaultVersionIsOne(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->assertSame(1, $book->version);
    }

    public function testPublishWithAuthorsSucceeds(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2]);

        $book->publish();

        $this->assertTrue($book->published);
    }

    public function testPublishWithoutAuthorsThrowsDomainException(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $book->publish();
    }

    public function testUpdateIsbnOnPublishedBookThrowsDomainException(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1]);
        $book->publish();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.isbn_change_published');

        $book->correctIsbn(new Isbn('979-10-90636-07-1'));
    }

    public function testUpdateIsbnOnDraftBookSucceeds(): void
    {
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
        $newIsbn = new Isbn('979-10-90636-07-1');

        $book->correctIsbn($newIsbn);

        $this->assertTrue($book->isbn->equals($newIsbn));
    }

    public function testUpdateSameIsbnOnPublishedBookSucceeds(): void
    {
        $isbn = new Isbn('978-3-16-148410-0');
        $book = Book::create('Title', new BookYear(2023, new \DateTimeImmutable()), $isbn, null, null);
        $book->replaceAuthors([1]);
        $book->publish();

        $book->rename('New Title');

        $this->assertSame('New Title', $book->title);
    }

    public function testThrowsExceptionOnEmptyTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        Book::create('', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testThrowsExceptionOnWhitespaceOnlyTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        Book::create('   ', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testThrowsExceptionOnTooLongTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        Book::create(str_repeat('A', 256), new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testUpdateThrowsExceptionOnEmptyTitle(): void
    {
        $book = Book::create('Valid Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        $book->rename('');
    }

    public function testUpdateThrowsExceptionOnTooLongTitle(): void
    {
        $book = Book::create('Valid Title', new BookYear(2023, new \DateTimeImmutable()), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        $book->rename(str_repeat('X', 256));
    }

    private function assignBookId(Book $book, int $id): void
    {
        $method = new \ReflectionMethod(Book::class, 'setId');
        $method->setAccessible(true);
        $method->invoke($book, $id);
    }
}
