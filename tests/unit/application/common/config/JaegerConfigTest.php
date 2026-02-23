<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\JaegerConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class JaegerConfigTest extends Unit
{
    public function testFromParamsSuccess(): void
    {
        $params = [
            'jaeger' => [
                'endpoint' => 'http://jaeger:4318/v1/traces',
                'serviceName' => 'yii2-book-catalog',
            ],
        ];

        $config = JaegerConfig::fromParams($params);

        $this->assertSame('http://jaeger:4318/v1/traces', $config->endpoint);
        $this->assertSame('yii2-book-catalog', $config->serviceName);
    }

    public function testFromParamsMissingSection(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: jaeger');

        JaegerConfig::fromParams([]);
    }
}
