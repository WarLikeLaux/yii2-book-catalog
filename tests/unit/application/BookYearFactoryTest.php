<?php

declare(strict_types=1);

namespace tests\unit\application;

use app\application\books\factories\BookYearFactory;
use app\domain\values\BookYear;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class BookYearFactoryTest extends Unit
{
    public function testCreateReturnsBookYear(): void
    {
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));

        $factory = new BookYearFactory($clock);
        $year = $factory->create(2023);

        $this->assertInstanceOf(BookYear::class, $year);
        $this->assertSame(2023, $year->value);
    }

    public function testCreateUsesClockForValidation(): void
    {
        // Сейчас "2024", поэтому 2025 валиден (currentYear + 1)
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2024-01-01'));

        $factory = new BookYearFactory($clock);
        $year = $factory->create(2025);

        $this->assertSame(2025, $year->value);
    }
}
