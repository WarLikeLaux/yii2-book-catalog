<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\RateLimitResult;

interface RateLimitServiceInterface
{
    public function isAllowed(string $identifier, int $limit, int $windowSeconds): RateLimitResult;
}
