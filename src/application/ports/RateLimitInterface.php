<?php

declare(strict_types=1);

namespace app\application\ports;

interface RateLimitInterface
{
    /** @return array{allowed: bool, current: int, limit: int, resetAt: int} */
    public function checkLimit(string $key, int $limit, int $windowSeconds): array;
}
