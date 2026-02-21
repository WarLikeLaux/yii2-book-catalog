<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\HealthReport;

interface HealthCheckRunnerInterface
{
    public function run(): HealthReport;
}
