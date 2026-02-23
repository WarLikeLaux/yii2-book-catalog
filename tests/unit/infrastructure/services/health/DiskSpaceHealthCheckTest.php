<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\health;

use app\infrastructure\services\health\DiskSpaceHealthCheck;
use Codeception\Test\Unit;

final class DiskSpaceHealthCheckTest extends Unit
{
    public function testCheckSuccess(): void
    {
        $check = new DiskSpaceHealthCheck(0.0);
        $result = $check->check();

        $this->assertSame('disk', $result->name);
        $this->assertTrue($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertArrayHasKey('free_gb', $result->details);
    }

    public function testCheckFailureDueToThreshold(): void
    {
        $check = new DiskSpaceHealthCheck(999999.0);
        $result = $check->check();

        $this->assertSame('disk', $result->name);
        $this->assertFalse($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertArrayHasKey('free_gb', $result->details);
    }
}
