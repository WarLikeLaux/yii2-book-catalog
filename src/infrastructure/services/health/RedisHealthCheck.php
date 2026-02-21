<?php

declare(strict_types=1);

namespace app\infrastructure\services\health;

use app\application\common\dto\HealthCheckResult;
use app\application\ports\HealthCheckInterface;
use Throwable;
use yii\redis\Connection;

final readonly class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(private Connection $redis)
    {
    }

    public function name(): string
    {
        return 'redis';
    }

    public function check(): HealthCheckResult
    {
        $start = microtime(true);

        try {
            $result = $this->redis->executeCommand('PING');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: $result !== null && $result !== false,
                latencyMs: $latency,
            );
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: false,
                latencyMs: $latency,
                details: ['error' => $e->getMessage()],
            );
        }
    }
}
