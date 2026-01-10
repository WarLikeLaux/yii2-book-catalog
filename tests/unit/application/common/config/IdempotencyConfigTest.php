<?php

declare(strict_types=1);

namespace tests\unit\application\common\config;

use app\application\common\config\IdempotencyConfig;
use app\application\common\exceptions\ConfigurationException;
use Codeception\Test\Unit;

final class IdempotencyConfigTest extends Unit
{
    public function testFromParamsBuildsConfig(): void
    {
        $config = IdempotencyConfig::fromParams([
            'idempotency' => [
                'ttl' => 60,
                'lockTimeout' => 1,
                'waitSeconds' => 2,
                'smsPhoneHashKey' => 'hash',
            ],
        ]);

        $this->assertSame(60, $config->ttl);
        $this->assertSame(1, $config->lockTimeout);
        $this->assertSame(2, $config->waitSeconds);
        $this->assertSame('hash', $config->smsPhoneHashKey);
    }

    public function testFromParamsThrowsWhenSectionMissing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: idempotency');

        IdempotencyConfig::fromParams([]);
    }

    public function testFromParamsThrowsOnInvalidValues(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: idempotency.ttl');

        IdempotencyConfig::fromParams([
            'idempotency' => [
                'ttl' => '60',
                'lockTimeout' => 1,
                'waitSeconds' => 1,
                'smsPhoneHashKey' => 'hash',
            ],
        ]);
    }

    public function testFromParamsThrowsOnInvalidSmsHashKeyType(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: idempotency.smsPhoneHashKey');

        IdempotencyConfig::fromParams([
            'idempotency' => [
                'ttl' => 60,
                'lockTimeout' => 1,
                'waitSeconds' => 1,
                'smsPhoneHashKey' => 123,
            ],
        ]);
    }

    public function testConstructorThrowsOnInvalidTtl(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: idempotency.ttl');

        $create = static fn() => new IdempotencyConfig(0, 1, 1, 'hash');
        $create();
    }

    public function testConstructorThrowsOnInvalidLockTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: idempotency.lockTimeout');

        $create = static fn() => new IdempotencyConfig(1, -1, 1, 'hash');
        $create();
    }

    public function testConstructorThrowsOnInvalidWaitSeconds(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid config: idempotency.waitSeconds');

        $create = static fn() => new IdempotencyConfig(1, 1, -1, 'hash');
        $create();
    }

    public function testConstructorThrowsOnEmptySmsHashKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Missing required config: idempotency.smsPhoneHashKey');

        $create = static fn() => new IdempotencyConfig(1, 1, 1, '');
        $create();
    }
}
