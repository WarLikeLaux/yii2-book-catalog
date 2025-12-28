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

    public function testThrowsExceptionOnIsbn13WithPrefixGarbage(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('abc9783161484100'); 
    }

    public function testThrowsExceptionOnIsbn13WithSuffixGarbage(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('9783161484100xyz');
    }

    public function testThrowsExceptionOnIsbn10WithPrefixGarbage(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('a0306406152');
    }

    public function testThrowsExceptionOnIsbn10WithSuffixGarbage(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('0306406152x');
    }

    public function testThrowsExceptionOnInvalidIsbn13Prefix(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('9773161484100');
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

    public function testGetFormattedReturnsFormattedIsbn13(): void
    {
        $isbn = new Isbn('9783161484100');
        $this->assertSame('978-3-16-148410-0', $isbn->getFormatted());
    }

    public function testGetFormattedReturnsRawValueForIsbn10(): void
    {
        $isbn = new Isbn('0306406152');
        $this->assertSame('0306406152', $isbn->getFormatted());
    }

    public function testGetFormattedReturnsFormattedIsbn13WithDistinctLastDigits(): void
    {
        $isbn = new Isbn('979-10-90636-07-1');
        $this->assertSame('979-1-09-063607-1', $isbn->getFormatted());
    }

    public function testThrowsExceptionOnIsbn13WithLetterInMiddle(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('978a000000002');
    }

    public function testThrowsExceptionOnIsbn10WithLetterInsteadOfZero(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('a306406152');
    }

    public function testThrowsExceptionOnIsbn10WithLetterYInsteadOfZeroAtEnd(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('000000000Y');
    }

    public function testThrowsExceptionOnIsbn10WithLetterYAtPosition8(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('00000000Y0');
    }
}