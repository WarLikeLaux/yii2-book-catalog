<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class HealthReport
{
    /**
     * @param array<int, HealthCheckResult> $checks
     */
    public function __construct(
        public bool $healthy,
        public array $checks,
        public string $version,
        public string $timestamp,
    ) {
    }
}
