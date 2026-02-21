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
}
