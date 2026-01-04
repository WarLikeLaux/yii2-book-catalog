<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Author;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;

final class AuthorTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $author = Author::create('Test FIO');

        $this->assertNull($author->id);
        $this->assertSame('Test FIO', $author->fio);

        $author->setId(123);
        $this->assertSame(123, $author->id);
    }

    public function testUpdate(): void
    {
        $author = Author::create('Old Name');
        $this->assertSame('Old Name', $author->fio);

        $author->update('New Name');
        $this->assertSame('New Name', $author->fio);
    }

    public function testConstructor(): void
    {
        $author = new Author(555, 'Direct Create');
        $this->assertSame(555, $author->id);
        $this->assertSame('Direct Create', $author->fio);
    }

    public function testThrowsExceptionOnEmptyFio(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_empty');
        Author::create('');
    }

    public function testThrowsExceptionOnWhitespaceOnlyFio(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_empty');
        Author::create('   ');
    }

    public function testThrowsExceptionOnTooShortFio(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_too_short');
        Author::create('A');
    }

    public function testThrowsExceptionOnSingleMultibyteCharFio(): void
    {
        $previousEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        try {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('author.error.fio_too_short');
            Author::create("\xD0\xAF");
        } finally {
            if ($previousEncoding !== false) {
                mb_internal_encoding($previousEncoding);
            }
        }
    }

    public function testAllowsMinLengthFio(): void
    {
        $author = Author::create('Ab');

        $this->assertSame('Ab', $author->fio);
    }

    public function testThrowsExceptionOnTooLongFio(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_too_long');
        Author::create(str_repeat('A', 256));
    }

    public function testAllowsMaxLengthFioWithMultibyte(): void
    {
        $fio = str_repeat("\xD0\xAF", 255);
        $author = Author::create($fio);

        $this->assertSame($fio, $author->fio);
    }

    public function testUpdateThrowsExceptionOnEmptyFio(): void
    {
        $author = Author::create('Valid Name');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_empty');
        $author->update('');
    }

    public function testUpdateThrowsExceptionOnTooShortFio(): void
    {
        $author = Author::create('Valid Name');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_too_short');
        $author->update('X');
    }
}
