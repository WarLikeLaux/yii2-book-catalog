<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\application\common\dto\RateLimitResult;
use app\infrastructure\adapters\SystemClock;
use app\infrastructure\repositories\RateLimitRepository;
use Codeception\Test\Unit;
use yii\redis\Connection;

final class RateLimitRepositoryTest extends Unit
{
    public function testCheckLimitAllowsRequestWhenBelowLimit(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(30);
        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertTrue($result->allowed);
    }

    public function testCheckLimitAllowsRequestWhenAtLimit(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(60);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);

        $this->assertTrue($result->allowed);
    }

    public function testCheckLimitDeniesRequestWhenExceedsLimit(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(61);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);

        $this->assertFalse($result->allowed);
    }

    public function testCheckLimitReturnsCorrectCurrentCount(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(45);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);

        $this->assertEquals(45, $result->current);
    }

    public function testCheckLimitReturnsCorrectResetTimestamp(): void
    {
        $before = time();
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(30);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);
        $after = time();

        $this->assertGreaterThanOrEqual($before + 60, $result->resetAt);
        $this->assertLessThanOrEqual($after + 60, $result->resetAt);
    }

    public function testCheckLimitCallsRedisMultipleTimes(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->expects($this->atLeast(1))
            ->method('executeCommand');

        $repository = new RateLimitRepository($redis, new SystemClock());
        $repository->checkLimit('192.168.1.1', 60, 60);
    }

    public function testCheckLimitWithDifferentKeys(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(10);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result1 = $repository->checkLimit('192.168.1.1', 60, 60);
        $result2 = $repository->checkLimit('192.168.1.2', 60, 60);

        $this->assertEquals(10, $result1->current);
        $this->assertEquals(10, $result2->current);
    }

    public function testCheckLimitReturnsCorrectLimit(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(50);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 100, 60);

        $this->assertEquals(100, $result->limit);
    }

    public function testCheckLimitReturnsAllFields(): void
    {
        $redis = $this->createMock(Connection::class);
        $redis->method('executeCommand')->willReturn(30);

        $repository = new RateLimitRepository($redis, new SystemClock());
        $result = $repository->checkLimit('192.168.1.1', 60, 60);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertObjectHasProperty('allowed', $result);
        $this->assertObjectHasProperty('current', $result);
        $this->assertObjectHasProperty('limit', $result);
        $this->assertObjectHasProperty('resetAt', $result);
    }
}
