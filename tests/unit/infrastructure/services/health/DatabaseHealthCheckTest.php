<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\health;

use app\infrastructure\services\health\DatabaseHealthCheck;
use Codeception\Test\Unit;
use Exception;
use yii\db\Command;
use yii\db\Connection;

final class DatabaseHealthCheckTest extends Unit
{
    public function testCheckSuccess(): void
    {
        $command = $this->createMock(Command::class);
        $command->expects($this->once())
            ->method('queryScalar')
            ->willReturn(1);

        $db = $this->createMock(Connection::class);
        $db->expects($this->once())
            ->method('createCommand')
            ->with('SELECT 1')
            ->willReturn($command);

        $check = new DatabaseHealthCheck($db);

        $result = $check->check();

        $this->assertSame('database', $result->name);
        $this->assertTrue($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertEmpty($result->details);
    }

    public function testCheckFailureDueToException(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects($this->once())
            ->method('createCommand')
            ->willThrowException(new Exception('DB connection failed'));

        $check = new DatabaseHealthCheck($db);

        $result = $check->check();

        $this->assertSame('database', $result->name);
        $this->assertFalse($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertSame(['error' => 'DB connection failed'], $result->details);
    }
}
