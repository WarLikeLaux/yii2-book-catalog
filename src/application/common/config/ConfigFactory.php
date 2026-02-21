<?php

declare(strict_types=1);

namespace app\application\common\config;

final readonly class ConfigFactory
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    public function idempotency(): IdempotencyConfig
    {
        return IdempotencyConfig::fromParams($this->params);
    }

    public function rateLimit(): RateLimitConfig
    {
        return RateLimitConfig::fromParams($this->params);
    }

    public function reports(): ReportsConfig
    {
        return ReportsConfig::fromParams($this->params);
    }

    public function storage(): StorageConfig
    {
        return StorageConfig::fromParams($this->params);
    }

    public function jaeger(): JaegerConfig
    {
        return JaegerConfig::fromParams($this->params);
    }

    public function apiPage(): ApiPageConfig
    {
        return ApiPageConfig::fromParams($this->params);
    }
}
