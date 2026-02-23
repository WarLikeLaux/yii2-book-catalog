<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\ApiPageConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class ApiPageConfigTest extends Unit
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => 8000,
        ]);

        $this->assertSame(8081, $config->swaggerPort);
        $this->assertSame(8000, $config->appPort);
    }

    public function testFromParamsThrowsWhenSwaggerPortMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: swaggerPort');

        ApiPageConfig::fromParams(['appPort' => 8000]);
    }

    public function testFromParamsThrowsWhenAppPortMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: appPort');

        ApiPageConfig::fromParams(['swaggerPort' => 8081]);
    }

    public function testFromParamsThrowsWhenSwaggerPortNotInt(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: swaggerPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => '8081',
            'appPort' => 8000,
        ]);
    }

    public function testFromParamsThrowsWhenSwaggerPortIsZero(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: swaggerPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => 0,
            'appPort' => 8000,
        ]);
    }

    public function testFromParamsThrowsWhenSwaggerPortIsNegative(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: swaggerPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => -1,
            'appPort' => 8000,
        ]);
    }

    public function testFromParamsThrowsWhenSwaggerPortExceedsMax(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: swaggerPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => 65536,
            'appPort' => 8000,
        ]);
    }

    public function testFromParamsAcceptsSwaggerPortAtBoundaries(): void
    {
        $configMin = ApiPageConfig::fromParams([
            'swaggerPort' => 1,
            'appPort' => 8000,
        ]);
        $this->assertSame(1, $configMin->swaggerPort);

        $configMax = ApiPageConfig::fromParams([
            'swaggerPort' => 65535,
            'appPort' => 8000,
        ]);
        $this->assertSame(65535, $configMax->swaggerPort);
    }

    public function testFromParamsThrowsWhenAppPortIsZero(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: appPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => 0,
        ]);
    }

    public function testFromParamsThrowsWhenAppPortIsNegative(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: appPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => -1,
        ]);
    }

    public function testFromParamsThrowsWhenAppPortExceedsMax(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: appPort');

        ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => 65536,
        ]);
    }

    public function testFromParamsAcceptsAppPortAtBoundaries(): void
    {
        $configMin = ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => 1,
        ]);
        $this->assertSame(1, $configMin->appPort);

        $configMax = ApiPageConfig::fromParams([
            'swaggerPort' => 8081,
            'appPort' => 65535,
        ]);
        $this->assertSame(65535, $configMax->appPort);
    }
}
