<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\health;

use app\application\common\dto\HealthCheckResult;
use app\application\ports\HealthCheckInterface;
use app\infrastructure\services\health\HealthCheckRunner;
use Codeception\Test\Unit;

final class HealthCheckRunnerTest extends Unit
{
    public function testRunSuccess(): void
    {
        $check1 = $this->createMock(HealthCheckInterface::class);
        $check1->method('check')
            ->willReturn(new HealthCheckResult('test1', true, 1.0));

        $check2 = $this->createMock(HealthCheckInterface::class);
        $check2->method('check')
            ->willReturn(new HealthCheckResult('test2', true, 2.0));

        $runner = new HealthCheckRunner([$check1, $check2], '1.0.0');
        $report = $runner->run();

        $this->assertTrue($report->healthy);
        $this->assertCount(2, $report->checks);
        $this->assertSame('1.0.0', $report->version);
        $this->assertNotEmpty($report->timestamp);
    }

    public function testRunFailure(): void
    {
        $check1 = $this->createMock(HealthCheckInterface::class);
        $check1->method('check')
            ->willReturn(new HealthCheckResult('test1', true, 1.0));

        $check2 = $this->createMock(HealthCheckInterface::class);
        $check2->method('check')
            ->willReturn(new HealthCheckResult('test2', false, 2.0));

        $runner = new HealthCheckRunner([$check1, $check2], '1.0.0');
        $report = $runner->run();

        $this->assertFalse($report->healthy);
        $this->assertCount(2, $report->checks);
        $this->assertSame('1.0.0', $report->version);
    }
}
