<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class IdempotencyConfig
{
    public function __construct(
        public int $ttl,
        public int $lockTimeout,
        public int $waitSeconds,
        public string $smsPhoneHashKey,
    ) {
        if ($this->ttl < 1) {
            throw new ConfigurationException('Invalid config: idempotency.ttl');
        }

        if ($this->lockTimeout < 0) {
            throw new ConfigurationException('Invalid config: idempotency.lockTimeout');
        }

        if ($this->waitSeconds < 0) {
            throw new ConfigurationException('Invalid config: idempotency.waitSeconds');
        }

        if ($this->smsPhoneHashKey === '') {
            throw new ConfigurationException('Invalid config: idempotency.smsPhoneHashKey');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $idempotency = $reader->requireSection('idempotency');
        $ttl = $reader->requireInt($idempotency, 'idempotency', 'ttl');
        $lockTimeout = $reader->requireInt($idempotency, 'idempotency', 'lockTimeout');
        $waitSeconds = $reader->requireInt($idempotency, 'idempotency', 'waitSeconds');
        $smsPhoneHashKey = $reader->requireString($idempotency, 'idempotency', 'smsPhoneHashKey');

        return new self(
            $ttl,
            $lockTimeout,
            $waitSeconds,
            $smsPhoneHashKey,
        );
    }
}
