<?php

declare(strict_types=1);

namespace tests\unit\domain\services;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;

final class BookPublicationPolicyTest extends Unit
{
    private BookPublicationPolicy $policy;

    protected function _before(): void
    {
        $this->policy = new BookPublicationPolicy();
    }

    public function testEnsureCanPublishWithAllRequirementsSucceeds(): void
    {
        $book = $this->createValidPublishableBook();

        $this->policy->ensureCanPublish($book);

        $this->assertTrue(true);
    }

    public function testEnsureCanPublishWithoutAuthorsThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            $this->validDescription(),
            new StoredFileReference('covers/test.jpg'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $this->policy->ensureCanPublish($book);
    }

    public function testEnsureCanPublishWithoutCoverThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            $this->validDescription(),
            null,
        );
        $book->replaceAuthors([1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_cover');

        $this->policy->ensureCanPublish($book);
    }

    public function testEnsureCanPublishWithNullDescriptionThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_short_description');

        $this->policy->ensureCanPublish($book);
    }

    public function testEnsureCanPublishWithShortDescriptionThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Short description less than 50 chars',
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_short_description');

        $this->policy->ensureCanPublish($book);
    }

    public function testEnsureCanPublishWithWhitespaceOnlyDescriptionThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            str_repeat(' ', 60),
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_short_description');

        $this->policy->ensureCanPublish($book);
    }

    public function testCanPublishReturnsTrueWithAllRequirements(): void
    {
        $book = $this->createValidPublishableBook();

        $this->assertTrue($this->policy->canPublish($book));
    }

    public function testCanPublishReturnsFalseWithoutAuthors(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            $this->validDescription(),
            new StoredFileReference('covers/test.jpg'),
        );

        $this->assertFalse($this->policy->canPublish($book));
    }

    public function testCanPublishReturnsFalseWithoutCover(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            $this->validDescription(),
            null,
        );
        $book->replaceAuthors([1]);

        $this->assertFalse($this->policy->canPublish($book));
    }

    public function testCanPublishReturnsFalseWithShortDescription(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Too short',
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1]);

        $this->assertFalse($this->policy->canPublish($book));
    }

    public function testCanPublishReturnsTrueWithExactlyMinimumDescription(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            str_repeat('a', 50),
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1]);

        $this->assertTrue($this->policy->canPublish($book));
    }

    private function createValidPublishableBook(): Book
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            $this->validDescription(),
            new StoredFileReference('covers/test.jpg'),
        );
        $book->replaceAuthors([1, 2]);

        return $book;
    }

    private function validDescription(): string
    {
        return 'This is a valid description that is long enough to pass the minimum requirement of 50 characters.';
    }
}
