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
        $year = new BookYear(2023, new \DateTimeImmutable('2024-01-01'));
        $this->assertSame(2023, $year->value);
    }

    public function testThrowsExceptionOnTooOldYear(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.too_old');
        new BookYear(1000, new \DateTimeImmutable('2024-01-01'));
    }

    public function testCanCreateYearFromFutureBoundary(): void
    {
        // Сейчас 2024. Год 2025 валиден (текущий + 1).
        $now = new \DateTimeImmutable('2024-01-01');
        $futureBoundary = 2025;

        $bookYear = new BookYear($futureBoundary, $now);
        $this->assertSame($futureBoundary, $bookYear->value);
    }

    public function testThrowsExceptionOnFutureYear(): void
    {
        // Сейчас 2024. Год 2026 невалиден.
        $now = new \DateTimeImmutable('2024-01-01');
        $tooFarFuture = 2026;

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');
        new BookYear($tooFarFuture, $now);
    }

    public function testToStringReturnsValueAsString(): void
    {
        $year = new BookYear(2023, new \DateTimeImmutable());
        $this->assertSame('2023', (string)$year);
    }

    public function testEqualsReturnsTrueForSameYear(): void
    {
        $now = new \DateTimeImmutable();
        $year1 = new BookYear(2023, $now);
        $year2 = new BookYear(2023, $now);

        $this->assertTrue($year1->equals($year2));
    }

    public function testEqualsReturnsFalseForDifferentYear(): void
    {
        $now = new \DateTimeImmutable();
        $year1 = new BookYear(2023, $now);
        $year2 = new BookYear(2024, $now);

        $this->assertFalse($year1->equals($year2));
    }
}
