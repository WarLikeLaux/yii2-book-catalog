<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class IsbnTest extends Unit
{
    public function testCanCreateValidIsbn13(): void
    {
        $isbn = new Isbn('978-3-16-148410-0');
        $this->assertSame('9783161484100', $isbn->value);
    }

    public function testCanCreateValidIsbn10(): void
    {
        $isbn = new Isbn('0-306-40615-2');
        $this->assertSame('0306406152', $isbn->value);
    }

    public function testThrowsExceptionOnInvalidFormat(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid ISBN format');
        new Isbn('invalid-isbn');
    }

    public function testThrowsExceptionOnInvalidChecksum(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid ISBN format');
        new Isbn('978-3-16-148410-1');
    }

    public function testToStringReturnsValue(): void
    {
        $isbn = new Isbn('978-3-16-148410-0');
        $this->assertSame('9783161484100', (string)$isbn);
    }
}
