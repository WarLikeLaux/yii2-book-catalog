<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\adapters\SystemClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function testNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $result = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
    }
}
