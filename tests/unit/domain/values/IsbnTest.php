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

    public function testIsbn10WithXCheckDigit(): void
    {
        $isbn = new Isbn('0-8044-2957-X');
        $this->assertSame('080442957X', $isbn->value);
    }

    public function testIsbn10WithLowercaseXCheckDigit(): void
    {
        $isbn = new Isbn('080442957x');
        $this->assertSame('080442957x', $isbn->value);
    }

    public function testIsbn13WithPrefix979(): void
    {
        $isbn = new Isbn('979-10-90636-07-1');
        $this->assertSame('9791090636071', $isbn->value);
    }

    public function testThrowsExceptionOnInvalidIsbn13Prefix(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('9770000000001');
    }

    public function testThrowsExceptionOnInvalidIsbn10Checksum(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('0306406151');
    }

    public function testThrowsExceptionOnTooShortIsbn(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('123');
    }

    public function testThrowsExceptionOnTooLongIsbn(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('12345678901234');
    }

    public function testNormalizesIsbnWithSpaces(): void
    {
        $isbn = new Isbn('978 3 16 148410 0');
        $this->assertSame('9783161484100', $isbn->value);
    }

    public function testThrowsExceptionOnIsbn10WithLettersInMiddle(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('12345ABCD0');
    }
}
