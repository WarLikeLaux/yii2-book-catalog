<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\health;

use app\infrastructure\services\health\QueueHealthCheck;
use Codeception\Test\Unit;
use Exception;
use yii\db\Command;
use yii\db\Connection;

final class QueueHealthCheckTest extends Unit
{
    public function testCheckSuccess(): void
    {
        $command = $this->createMock(Command::class);
        $command->expects($this->once())
            ->method('queryScalar')
            ->willReturn(5);

        $db = $this->createMock(Connection::class);
        $db->expects($this->once())
            ->method('createCommand')
            ->with('SELECT COUNT(*) FROM {{%queue}} WHERE done_at IS NULL')
            ->willReturn($command);

        $check = new QueueHealthCheck($db);

        $result = $check->check();

        $this->assertSame('queue', $result->name);
        $this->assertTrue($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertSame(['pending_jobs' => 5], $result->details);
    }

    public function testCheckFailureDueToException(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects($this->once())
            ->method('createCommand')
            ->willThrowException(new Exception('Queue count error'));

        $check = new QueueHealthCheck($db);

        $result = $check->check();

        $this->assertSame('queue', $result->name);
        $this->assertFalse($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertSame(['error' => 'Queue count error'], $result->details);
    }
}
