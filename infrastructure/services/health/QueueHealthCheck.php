<?php

declare(strict_types=1);

namespace app\infrastructure\services\health;

use app\application\common\dto\HealthCheckResult;
use app\application\ports\HealthCheckInterface;
use Throwable;
use yii\db\Connection;

final readonly class QueueHealthCheck implements HealthCheckInterface
{
    public function __construct(private Connection $db)
    {
    }

    public function name(): string
    {
        return 'queue';
    }

    public function check(): HealthCheckResult
    {
        $start = microtime(true);

        try {
            $count = $this->db->createCommand('SELECT COUNT(*) FROM {{%queue}} WHERE done_at IS NULL')->queryScalar();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: true,
                latencyMs: $latency,
                details: ['pending_jobs' => is_scalar($count) ? (int) $count : 0],
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
