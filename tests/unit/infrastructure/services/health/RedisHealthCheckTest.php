<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\health;

use app\infrastructure\services\health\RedisHealthCheck;
use Codeception\Test\Unit;
use Exception;
use yii\redis\Connection;

final class RedisHealthCheckTest extends Unit
{
    public function testCheckSuccess(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->expects($this->once())
            ->method('executeCommand')
            ->with('PING')
            ->willReturn('PONG');

        $check = new RedisHealthCheck($redis);
        $result = $check->check();

        $this->assertSame('redis', $result->name);
        $this->assertTrue($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertEmpty($result->details);
    }

    public function testCheckFailureDueToException(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->expects($this->once())
            ->method('executeCommand')
            ->with('PING')
            ->willThrowException(new Exception('Redis error'));

        $check = new RedisHealthCheck($redis);
        $result = $check->check();

        $this->assertSame('redis', $result->name);
        $this->assertFalse($result->healthy);
        $this->assertGreaterThanOrEqual(0.0, $result->latencyMs);
        $this->assertSame(['error' => 'Redis error'], $result->details);
    }
}
