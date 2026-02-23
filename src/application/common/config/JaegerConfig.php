<?php

declare(strict_types=1);

namespace app\application\common\config;

final readonly class JaegerConfig
{
    public function __construct(
        public string $endpoint,
        public string $serviceName,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromParams(array $params): self
    {
        $reader = new ConfigReader($params);
        $section = $reader->requireSection('jaeger');

        return new self(
            $reader->requireString($section, 'jaeger', 'endpoint'),
            $reader->requireString($section, 'jaeger', 'serviceName'),
        );
    }
}
