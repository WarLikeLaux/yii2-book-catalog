<?php

declare(strict_types=1);

namespace app\infrastructure\services\health;

use app\application\common\dto\HealthCheckResult;
use app\application\ports\HealthCheckInterface;
use Throwable;
use yii\db\Connection;

final readonly class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(private Connection $db)
    {
    }

    public function name(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        $start = microtime(true);

        try {
            $result = $this->db->createCommand('SELECT 1')->queryScalar();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return new HealthCheckResult(
                name: $this->name(),
                healthy: $result !== false,
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
