<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class HealthCheckResult
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $name,
        public bool $healthy,
        public float $latencyMs,
        public array $details = [],
    ) {
    }
}
