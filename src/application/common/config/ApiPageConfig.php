<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class ApiPageConfig
{
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

        if (!is_int($swaggerPort) || $swaggerPort <= 0 || $swaggerPort > 65535) {
            throw new ConfigurationException('Invalid config: swaggerPort');
        }

        if (!is_int($appPort) || $appPort <= 0 || $appPort > 65535) {
            throw new ConfigurationException('Invalid config: appPort');
        }

        return new self($swaggerPort, $appPort);
    }
}
