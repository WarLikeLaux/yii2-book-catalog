<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\RateLimitConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class RateLimitConfigTest extends Unit
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = RateLimitConfig::fromParams([
            'rateLimit' => [
                'limit' => 10,
                'window' => 30,
            ],
        ]);

        $this->assertSame(10, $config->limit);
        $this->assertSame(30, $config->window);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: rateLimit');

        RateLimitConfig::fromParams([]);
    }

    public function testFromParamsThrowsWhenLimitInvalid(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: rateLimit.limit');

        RateLimitConfig::fromParams([
            'rateLimit' => [
                'limit' => '10',
                'window' => 30,
            ],
        ]);
    }

    public function testConstructorThrowsWhenLimitTooSmall(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: rateLimit.limit');

        $create = static fn() => new RateLimitConfig(0, 1);
        $create();
    }

    public function testConstructorThrowsWhenWindowTooSmall(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: rateLimit.window');

        $create = static fn() => new RateLimitConfig(1, 0);
        $create();
    }

    public function testConstructorAllowsWindowOne(): void
    {
        $config = new RateLimitConfig(10, 1);

        $this->assertSame(1, $config->window);
    }
}
