<?php

declare(strict_types=1);

namespace tests\unit\application\common;

use app\application\common\dto\RateLimitResult;
use app\application\common\RateLimitService;
use app\application\ports\RateLimitInterface;
use Codeception\Test\Unit;

final class RateLimitServiceTest extends Unit
{
    public function testIsAllowedReturnsTrueWhenBelowLimit(): void
    {
        $repository = $this->createMock(RateLimitInterface::class);
        $repository->method('checkLimit')
            ->willReturn([
                'allowed' => true,
                'current' => 30,
                'limit' => 60,
                'resetAt' => 1735929661,
            ]);

        $service = new RateLimitService($repository);
        $result = $service->isAllowed('192.168.1.1', 60, 60);

        $this->assertTrue($result->allowed);
        $this->assertEquals(30, $result->current);
        $this->assertEquals(60, $result->limit);
        $this->assertIsInt($result->resetAt);
    }

    public function testIsAllowedReturnsFalseWhenExceedsLimit(): void
    {
        $repository = $this->createMock(RateLimitInterface::class);
        $repository->method('checkLimit')
            ->willReturn([
                'allowed' => false,
                'current' => 60,
                'limit' => 60,
                'resetAt' => 1735929661,
            ]);

        $service = new RateLimitService($repository);
        $result = $service->isAllowed('192.168.1.1', 60, 60);

        $this->assertFalse($result->allowed);
        $this->assertEquals(60, $result->current);
        $this->assertEquals(60, $result->limit);
        $this->assertIsInt($result->resetAt);
    }

    public function testIsAllowedReturnsCorrectCurrentCount(): void
    {
        $repository = $this->createMock(RateLimitInterface::class);
        $repository->method('checkLimit')
            ->willReturn([
                'allowed' => true,
                'current' => 45,
                'limit' => 60,
                'resetAt' => 1735929661,
            ]);

        $service = new RateLimitService($repository);
        $result = $service->isAllowed('192.168.1.1', 60, 60);

        $this->assertEquals(45, $result->current);
    }

    public function testIsAllowedReturnsCorrectResetAt(): void
    {
        $repository = $this->createMock(RateLimitInterface::class);
        $repository->method('checkLimit')
            ->willReturn([
                'allowed' => true,
                'current' => 30,
                'limit' => 60,
                'resetAt' => 1735929661,
            ]);

        $service = new RateLimitService($repository);
        $result = $service->isAllowed('192.168.1.1', 60, 60);

        $this->assertEquals(1735929661, $result->resetAt);
        $this->assertIsInt($result->resetAt);
    }

    public function testIsAllowedReturnsRateLimitResultDto(): void
    {
        $repository = $this->createMock(RateLimitInterface::class);
        $repository->method('checkLimit')
            ->willReturn([
                'allowed' => true,
                'current' => 30,
                'limit' => 60,
                'resetAt' => 1735929661,
            ]);

        $service = new RateLimitService($repository);
        $result = $service->isAllowed('192.168.1.1', 60, 60);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertObjectHasProperty('allowed', $result);
        $this->assertObjectHasProperty('current', $result);
        $this->assertObjectHasProperty('limit', $result);
        $this->assertObjectHasProperty('resetAt', $result);
    }
}
