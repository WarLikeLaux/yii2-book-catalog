<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\RateLimitResult;

interface RateLimitInterface
{
    public function checkLimit(string $key, int $limit, int $windowSeconds): RateLimitResult;
}
