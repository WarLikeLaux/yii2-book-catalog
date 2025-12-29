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

        $this->assertNull($author->getId());
        $this->assertSame('Test FIO', $author->getFio());

        $author->setId(123);
        $this->assertSame(123, $author->getId());
    }

    public function testUpdate(): void
    {
        $author = Author::create('Old Name');
        $this->assertSame('Old Name', $author->getFio());

        $author->update('New Name');
        $this->assertSame('New Name', $author->getFio());
    }

    public function testConstructor(): void
    {
        $author = new Author(555, 'Direct Create');
        $this->assertSame(555, $author->getId());
        $this->assertSame('Direct Create', $author->getFio());
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

    public function testThrowsExceptionOnTooLongFio(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.fio_too_long');
        Author::create(str_repeat('A', 256));
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
