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

    public function testCanCreateYearAtMinimumBoundary(): void
    {
        $year = new BookYear(1001);

        $this->assertSame(1001, $year->value);
    }

    public function testToStringReturnsValueAsString(): void
    {
        $year = new BookYear(2023);

        $this->assertSame('2023', (string) $year);
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

    public function testCanCreateYearAtFutureBoundary(): void
    {
        $year = new BookYear(2025, 2024);

        $this->assertSame(2025, $year->value);
    }

    public function testThrowsExceptionOnFutureYear(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');

        new BookYear(2026, 2024);
    }

    public function testSkipsValidationWithoutCurrentYear(): void
    {
        $year = new BookYear(3000);

        $this->assertSame(3000, $year->value);
    }
}
