<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\ReportsConfig;
use app\application\common\exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class ReportsConfigTest extends TestCase
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = ReportsConfig::fromParams([
            'reports' => [
                'cacheTtl' => 120,
            ],
        ]);

        $this->assertSame(120, $config->cacheTtl);
    }

    public function testFromParamsThrowsWhenCacheTtlTooLow(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('reports.cacheTtl');

        ReportsConfig::fromParams([
            'reports' => [
                'cacheTtl' => 0,
            ],
        ]);
    }

    public function testFromParamsThrowsWhenCacheTtlTooHigh(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('reports.cacheTtl');

        ReportsConfig::fromParams([
            'reports' => [
                'cacheTtl' => 86401,
            ],
        ]);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: reports');

        ReportsConfig::fromParams([]);
    }

    public function testFromParamsValidatesBoundaryCacheTtl(): void
    {
        $configMin = ReportsConfig::fromParams(['reports' => ['cacheTtl' => 1]]);
        $this->assertSame(1, $configMin->cacheTtl);

        $configMax = ReportsConfig::fromParams(['reports' => ['cacheTtl' => 86400]]);
        $this->assertSame(86400, $configMax->cacheTtl);
    }
}
