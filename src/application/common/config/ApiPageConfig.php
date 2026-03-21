<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class ApiPageConfig
{
    private const int MAX_PORT_NUMBER = 65535;

    public function __construct(
        public int $swaggerPort,
        public int $appPort,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $swaggerPort = $params['swaggerPort'] ?? null;
        $appPort = $params['appPort'] ?? null;

        if (!is_int($swaggerPort) || $swaggerPort <= 0 || $swaggerPort > self::MAX_PORT_NUMBER) {
            throw new ConfigurationException('Invalid config: swaggerPort');
        }

        if (!is_int($appPort) || $appPort <= 0 || $appPort > self::MAX_PORT_NUMBER) {
            throw new ConfigurationException('Invalid config: appPort');
        }

        return new self($swaggerPort, $appPort);
    }
}
