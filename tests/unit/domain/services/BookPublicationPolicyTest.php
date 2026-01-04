<?php

declare(strict_types=1);

namespace tests\unit\domain\services;

use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class BookPublicationPolicyTest extends Unit
{
    private BookPublicationPolicy $policy;

    protected function _before(): void
    {
        $this->policy = new BookPublicationPolicy();
    }

    public function testEnsureCanPublishWithAuthorsSucceeds(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Description',
            null,
        );
        $book->replaceAuthors([1, 2]);

        $this->policy->ensureCanPublish($book);

        $this->assertTrue(true);
    }

    public function testEnsureCanPublishWithoutAuthorsThrowsException(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Description',
            null,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $this->policy->ensureCanPublish($book);
    }

    public function testCanPublishReturnsTrueWithAuthors(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $book->replaceAuthors([1]);

        $this->assertTrue($this->policy->canPublish($book));
    }

    public function testCanPublishReturnsFalseWithoutAuthors(): void
    {
        $book = Book::create(
            'Test Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null,
        );

        $this->assertFalse($this->policy->canPublish($book));
    }
}
