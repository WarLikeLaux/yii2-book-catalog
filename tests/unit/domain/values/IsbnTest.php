<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class IsbnTest extends Unit
{
    public static function validIsbnProvider(): array
    {
        return [
            'isbn13-with-dashes' => ['978-3-16-148410-0', '9783161484100'],
            'isbn10-with-dashes' => ['0-306-40615-2', '0306406152'],
            'isbn10-with-x-checkdigit' => ['0-8044-2957-X', '080442957X'],
            'isbn10-with-lowercase-x' => ['080442957x', '080442957x'],
            'isbn13-with-979-prefix' => ['979-10-90636-07-1', '9791090636071'],
            'isbn13-with-spaces' => ['978 3 16 148410 0', '9783161484100'],
        ];
    }

    public static function invalidFormatProvider(): array
    {
        return [
            'completely-invalid' => ['invalid-isbn'],
            'isbn13-with-prefix-garbage' => ['abc9783161484100'],
            'isbn13-with-suffix-garbage' => ['9783161484100xyz'],
            'isbn10-with-prefix-garbage' => ['a0306406152'],
            'isbn10-with-suffix-garbage' => ['0306406152x'],
            'isbn10-with-invalid-ninth-char' => ['00000000B8'],
            'isbn13-invalid-prefix' => ['9773161484100'],
            'isbn13-valid-checksum-invalid-prefix' => ['9771234567898'],
            'isbn13-invalid-checksum' => ['978-3-16-148410-1'],
            'isbn10-invalid-checksum' => ['0306406151'],
            'too-short' => ['123'],
            'too-long' => ['12345678901234'],
            'isbn10-letters-in-middle' => ['12345ABCD0'],
            'isbn13-letter-in-middle' => ['978a000000002'],
            'isbn10-letter-instead-of-zero' => ['a306406152'],
            'isbn10-letter-y-at-end' => ['000000000Y'],
            'isbn10-invalid-checkdigit' => ['000000000F'],
            'isbn10-letter-y-at-position-8' => ['00000000Y0'],
        ];
    }

    public static function equalsProvider(): array
    {
        return [
            'same-isbn-different-formats' => [
                '978-3-16-148410-0',
                '9783161484100',
                true,
            ],
            'different-isbns' => [
                '978-3-16-148410-0',
                '979-10-90636-07-1',
                false,
            ],
        ];
    }

    public static function formattedProvider(): array
    {
        return [
            'isbn13-formatted' => ['9783161484100', '978-3-16-148410-0'],
            'isbn10-raw' => ['0306406152', '0306406152'],
            'isbn13-distinct-last-digits' => ['979-10-90636-07-1', '979-1-09-063607-1'],
        ];
    }

    /**
     * @dataProvider validIsbnProvider
     */
    public function testCanCreateValidIsbn(string $input, string $expected): void
    {
        $isbn = new Isbn($input);
        $this->assertSame($expected, $isbn->value);
    }

    /**
     * @dataProvider invalidFormatProvider
     */
    public function testThrowsOnInvalidFormat(string $invalidIsbn): void
    {
        $this->expectException(DomainException::class);
        if (in_array($invalidIsbn, ['invalid-isbn', '978-3-16-148410-1'])) {
            $this->expectExceptionMessage('isbn.error.invalid_format');
        }
        new Isbn($invalidIsbn);
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEqualsWorks(string $isbn1Input, string $isbn2Input, bool $expected): void
    {
        $isbn1 = new Isbn($isbn1Input);
        $isbn2 = new Isbn($isbn2Input);
        $this->assertSame($expected, $isbn1->equals($isbn2));
    }

    /**
     * @dataProvider formattedProvider
     */
    public function testGetFormattedWorks(string $input, string $expected): void
    {
        $isbn = new Isbn($input);
        $this->assertSame($expected, $isbn->getFormatted());
    }

    public function testToStringReturnsValue(): void
    {
        $isbn = new Isbn('978-3-16-148410-0');
        $this->assertSame('9783161484100', (string)$isbn);
    }
}
