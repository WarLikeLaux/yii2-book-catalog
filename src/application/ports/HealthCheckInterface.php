<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\HealthCheckResult;

interface HealthCheckInterface
{
    public function name(): string;

    public function check(): HealthCheckResult;
}
