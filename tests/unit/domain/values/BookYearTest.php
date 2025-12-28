<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\BookYear;
use Codeception\Test\Unit;

final class BookYearTest extends Unit
{
    public function testCanCreateValidYear(): void
    {
        $year = new BookYear(2023);
        $this->assertSame(2023, $year->value);
    }

    public function testThrowsExceptionOnTooOldYear(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.too_old');
        new BookYear(1000);
    }

    public function testCanCreateYearFromFutureBoundary(): void
    {
        $year = (int)date('Y') + 1;
        $bookYear = new BookYear($year);
        $this->assertSame($year, $bookYear->value);
    }

    public function testThrowsExceptionOnFutureYear(): void
    {
        $futureYear = (int)date('Y') + 2;
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');
        new BookYear($futureYear);
    }

    public function testToStringReturnsValueAsString(): void
    {
        $year = new BookYear(2023);
        $this->assertSame('2023', (string)$year);
    }

    public function testEqualsReturnsTrueForSameYear(): void
    {
        $year1 = new BookYear(2023);
        $year2 = new BookYear(2023);

        $this->assertTrue($year1->equals($year2));
    }

    public function testEqualsReturnsFalseForDifferentYear(): void
    {
        $year1 = new BookYear(2023);
        $year2 = new BookYear(2024);

        $this->assertFalse($year1->equals($year2));
    }
}
