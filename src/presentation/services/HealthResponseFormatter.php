<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\common\dto\HealthReport;

final readonly class HealthResponseFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function format(HealthReport $report): array
    {
        $checks = [];

        foreach ($report->checks as $check) {
            $checks[$check->name] = array_merge(
                ['status' => $check->healthy ? 'up' : 'down', 'latency_ms' => $check->latencyMs],
                $check->details,
            );
        }

        return [
            'status' => $report->healthy ? 'healthy' : 'unhealthy',
            'timestamp' => $report->timestamp,
            'version' => $report->version,
            'checks' => $checks,
        ];
    }
}
