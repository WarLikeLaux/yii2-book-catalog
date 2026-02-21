<?php

declare(strict_types=1);

namespace app\infrastructure\services\health;

use app\application\common\dto\HealthReport;
use app\application\ports\HealthCheckInterface;
use app\application\ports\HealthCheckRunnerInterface;

final readonly class HealthCheckRunner implements HealthCheckRunnerInterface
{
    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        private iterable $checks,
        private string $version,
    ) {
    }

    public function run(): HealthReport
    {
        $healthy = true;
        $results = [];

        foreach ($this->checks as $check) {
            $result = $check->check();

            if (!$result->healthy) {
                $healthy = false;
            }

            $results[] = $result;
        }

        return new HealthReport(
            healthy: $healthy,
            checks: $results,
            version: $this->version,
            timestamp: date('c'),
        );
    }
}
