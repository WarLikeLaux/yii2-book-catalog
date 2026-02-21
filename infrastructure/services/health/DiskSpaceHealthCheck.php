<?php

declare(strict_types=1);

namespace app\infrastructure\services\health;

use app\application\common\dto\HealthCheckResult;
use app\application\ports\HealthCheckInterface;
use Throwable;

final readonly class DiskSpaceHealthCheck implements HealthCheckInterface
{
    public function __construct(private float $thresholdGb)
    {
    }

    public function name(): string
    {
        return 'disk';
    }

    public function check(): HealthCheckResult
    {
        $start = microtime(true);

        try {
            $freeBytes = disk_free_space('/');
            $freeGb = $freeBytes !== false ? round($freeBytes / 1024 / 1024 / 1024, 2) : 0.0;
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: $freeBytes !== false && $freeGb >= $this->thresholdGb,
                latencyMs: $latency,
                details: ['free_gb' => $freeGb],
            );
        // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: false,
                latencyMs: $latency,
                details: ['error' => $e->getMessage()],
            );
        }
        // @codeCoverageIgnoreEnd
    }
}
