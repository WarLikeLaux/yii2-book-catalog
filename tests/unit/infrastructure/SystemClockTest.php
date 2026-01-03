<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\adapters\SystemClock;
use Codeception\Test\Unit;
use DateTimeImmutable;

final class SystemClockTest extends Unit
{
    public function testNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $result = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
    }
}
