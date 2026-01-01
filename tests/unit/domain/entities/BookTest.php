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
        $year = new BookYear(2023);
        $isbn = new Isbn('978-3-16-148410-0');

        $book = Book::create('Title', $year, $isbn, 'Desc', 'http://url.com');

        $this->assertNull($book->getId());
        $this->assertSame('Title', $book->getTitle());
        $this->assertSame($year, $book->getYear());
        $this->assertSame($isbn, $book->getIsbn());
        $this->assertSame('Desc', $book->getDescription());
        $this->assertSame('http://url.com', $book->getCoverUrl());
        $this->assertSame([], $book->getAuthorIds());
        $this->assertSame(1, $book->getVersion());
    }

    public function testUpdate(): void
    {
        $book = Book::reconstitute(
            1,
            'Old Title',
            new BookYear(2020),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
            [],
            false,
            1
        );

        $newYear = new BookYear(2024);
        $newIsbn = new Isbn('978-3-16-148410-0');

        $book->update('New Title', $newYear, $newIsbn, 'New Desc', 'http://new.com');

        $this->assertSame('New Title', $book->getTitle());
        $this->assertSame($newYear, $book->getYear());
        $this->assertSame('New Desc', $book->getDescription());
        $this->assertSame('http://new.com', $book->getCoverUrl());

        $book->update('Title 2', $newYear, $newIsbn, 'Desc 2', null);
        $this->assertSame('http://new.com', $book->getCoverUrl(), 'Cover URL should not change if null passed');
    }

    public function testReplaceAuthors(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $book->replaceAuthors([1, 2, 3]);

        $this->assertSame([1, 2, 3], $book->getAuthorIds());
    }

    public function testAddAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $book->addAuthor(1);
        $book->addAuthor(2);

        $this->assertSame([1, 2], $book->getAuthorIds());
    }

    public function testAddAuthorIsIdempotent(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $book->addAuthor(1);
        $book->addAuthor(1);

        $this->assertSame([1], $book->getAuthorIds());
    }

    public function testAddAuthorThrowsExceptionOnInvalidId(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_author_id');

        $book->addAuthor(0);
    }

    public function testRemoveAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2, 3]);

        $book->removeAuthor(2);

        $this->assertSame([1, 3], $book->getAuthorIds());
    }

    public function testRemoveAuthorIsIdempotent(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2]);

        $book->removeAuthor(3);

        $this->assertSame([1, 2], $book->getAuthorIds());
    }

    public function testHasAuthor(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $book->addAuthor(1);

        $this->assertTrue($book->hasAuthor(1));
        $this->assertFalse($book->hasAuthor(2));
    }

    public function testSetId(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->assignBookId($book, 100);
        $this->assertSame(100, $book->getId());

        $this->assignBookId($book, 100);

        $this->expectException(\RuntimeException::class);
        $this->assignBookId($book, 200);
    }

    public function testIncrementVersion(): void
    {
        $book = Book::reconstitute(
            1,
            'Title',
            new BookYear(2023),
            new Isbn('978-3-16-148410-0'),
            null,
            null,
            [],
            false,
            5
        );

        $book->incrementVersion();

        $this->assertSame(6, $book->getVersion());
    }

    public function testIsPublishedReturnsFalseByDefault(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->assertFalse($book->isPublished());
    }

    public function testDefaultVersionIsOne(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->assertSame(1, $book->getVersion());
    }

    public function testPublishWithAuthorsSucceeds(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1, 2]);

        $book->publish();

        $this->assertTrue($book->isPublished());
    }

    public function testPublishWithoutAuthorsThrowsDomainException(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $book->publish();
    }

    public function testUpdateIsbnOnPublishedBookThrowsDomainException(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $book->replaceAuthors([1]);
        $book->publish();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.isbn_change_published');

        $book->update('Title', new BookYear(2023), new Isbn('979-10-90636-07-1'), null, null);
    }

    public function testUpdateIsbnOnDraftBookSucceeds(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        $newIsbn = new Isbn('979-10-90636-07-1');

        $book->update('Title', new BookYear(2023), $newIsbn, null, null);

        $this->assertTrue($book->getIsbn()->equals($newIsbn));
    }

    public function testUpdateSameIsbnOnPublishedBookSucceeds(): void
    {
        $isbn = new Isbn('978-3-16-148410-0');
        $book = Book::create('Title', new BookYear(2023), $isbn, null, null);
        $book->replaceAuthors([1]);
        $book->publish();

        $book->update('New Title', new BookYear(2024), new Isbn('978-3-16-148410-0'), 'Desc', null);

        $this->assertSame('New Title', $book->getTitle());
    }

    public function testThrowsExceptionOnEmptyTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        Book::create('', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testThrowsExceptionOnWhitespaceOnlyTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        Book::create('   ', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testThrowsExceptionOnTooLongTitle(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        Book::create(str_repeat('A', 256), new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testUpdateThrowsExceptionOnEmptyTitle(): void
    {
        $book = Book::create('Valid Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_empty');

        $book->update('', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
    }

    public function testUpdateThrowsExceptionOnTooLongTitle(): void
    {
        $book = Book::create('Valid Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.title_too_long');

        $book->update(str_repeat('X', 256), new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
    }

    private function assignBookId(Book $book, int $id): void
    {
        $method = new \ReflectionMethod(Book::class, 'setId');
        $method->setAccessible(true);
        $method->invoke($book, $id);
    }
}
