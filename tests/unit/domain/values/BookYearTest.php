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
        $this->expectExceptionMessage('Invalid year: must be greater than 1000');
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
        $this->expectExceptionMessage('Invalid year: cannot be in the future');
        new BookYear($futureYear);
    }
}
