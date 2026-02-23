<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\ConfigFactory;
use Codeception\Test\Unit;

final class ConfigFactoryTest extends Unit
{
    public function testBuildsAllConfigs(): void
    {
        $factory = new ConfigFactory([
            'idempotency' => [
                'ttl' => 10,
                'lockTimeout' => 0,
                'waitSeconds' => 5,
                'smsPhoneHashKey' => 'hash',
            ],
            'rateLimit' => [
                'limit' => 10,
                'window' => 30,
            ],
            'reports' => [
                'cacheTtl' => 120,
            ],
            'storage' => [
                'basePath' => '@app/web/uploads',
                'baseUrl' => '/uploads',
                'placeholderUrl' => 'https://example.com/{seed}',
            ],
            'jaeger' => [
                'endpoint' => 'http://jaeger:4318/v1/traces',
                'serviceName' => 'yii2-book-catalog',
            ],
        ]);

        $this->assertSame(10, $factory->idempotency()->ttl);
        $this->assertSame(30, $factory->rateLimit()->window);
        $this->assertSame(120, $factory->reports()->cacheTtl);
        $this->assertSame('/uploads', $factory->storage()->baseUrl);
        $this->assertSame('http://jaeger:4318/v1/traces', $factory->jaeger()->endpoint);
    }
}
