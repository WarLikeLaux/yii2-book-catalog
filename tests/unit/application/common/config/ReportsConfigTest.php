<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\ReportsConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class ReportsConfigTest extends Unit
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

    public function testFromParamsDefaultsWhenOutOfRange(): void
    {
        $config = ReportsConfig::fromParams([
            'reports' => [
                'cacheTtl' => 0,
            ],
        ]);

        $this->assertSame(3600, $config->cacheTtl);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: reports');

        ReportsConfig::fromParams([]);
    }
}
