<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\filters;

use app\application\common\config\RateLimitConfig;
use app\application\common\dto\RateLimitResult;
use app\application\common\RateLimitServiceInterface;
use app\presentation\common\filters\RateLimitFilter;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Yii;
use yii\base\Action;
use yii\web\Request;
use yii\web\Response;

final class RateLimitFilterTest extends Unit
{
    private const FIXED_NOW = '2024-01-15 12:00:00';

    private ClockInterface&MockObject $clock;

    protected function _before(): void
    {
        $request = new Request();
        Yii::$app->set('request', $request);
        Yii::$app->set('response', new Response());

        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable(self::FIXED_NOW));
    }

    public function testBeforeActionAllowsRequestWhenBelowLimit(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: true,
                current: 30,
                limit: 60,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $result = $filter->beforeAction($this->createMock(Action::class));

        $this->assertTrue($result);
        $this->assertSame('60', Yii::$app->response->getHeaders()->get('X-RateLimit-Limit'));
    }

    public function testBeforeActionDeniesRequestWhenExceedsLimit(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: false,
                current: 61,
                limit: 60,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $result = $filter->beforeAction($this->createMock(Action::class));

        $this->assertFalse($result);
        $this->assertSame(429, Yii::$app->response->statusCode);
    }

    public function testBeforeActionSetsRateLimitHeaders(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: true,
                current: 25,
                limit: 100,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $filter->beforeAction($this->createMock(Action::class));

        $this->assertSame('100', Yii::$app->response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertSame('75', Yii::$app->response->getHeaders()->get('X-RateLimit-Remaining'));
    }

    public function testBeforeActionReturns429WithRetryAfter(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: false,
                current: 101,
                limit: 100,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $filter->beforeAction($this->createMock(Action::class));

        $this->assertSame(429, Yii::$app->response->statusCode);
        $this->assertSame('60', Yii::$app->response->getHeaders()->get('Retry-After'));
    }

    public function testBeforeActionReturnsJsonBodyOn429(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: false,
                current: 101,
                limit: 100,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $filter->beforeAction($this->createMock(Action::class));

        $this->assertSame(429, Yii::$app->response->statusCode);
        $data = Yii::$app->response->data;
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame(60, $data['retryAfter']);
    }

    public function testBeforeActionHandlesNullIp(): void
    {
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->never())
            ->method('isAllowed');

        unset($_SERVER['REMOTE_ADDR']);
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $result = $filter->beforeAction($this->createMock(Action::class));

        $this->assertTrue($result);
    }

    public function testBeforeActionCalculatesCorrectRemaining(): void
    {
        $resetTime = $this->getFixedNowTimestamp() + 60;
        $service = $this->createMock(RateLimitServiceInterface::class);
        $service->expects($this->once())
            ->method('isAllowed')
            ->willReturn(new RateLimitResult(
                allowed: false,
                current: 101,
                limit: 100,
                resetAt: $resetTime,
            ));

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $filter = new RateLimitFilter($service, $this->createConfig(), $this->clock);
        $filter->beforeAction($this->createMock(Action::class));

        $remaining = (int)Yii::$app->response->getHeaders()->get('X-RateLimit-Remaining');
        $this->assertSame(0, $remaining);
    }

    private function createConfig(): RateLimitConfig
    {
        return new RateLimitConfig(60, 60);
    }

    private function getFixedNowTimestamp(): int
    {
        return (new DateTimeImmutable(self::FIXED_NOW))->getTimestamp();
    }
}
