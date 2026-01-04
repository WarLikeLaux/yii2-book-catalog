<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $current,
        public int $limit,
        public int $resetAt
    ) {
    }
}
