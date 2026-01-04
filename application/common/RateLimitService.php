<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\RateLimitResult;
use app\application\ports\RateLimitInterface;

final readonly class RateLimitService implements RateLimitServiceInterface
{
    public function __construct(
        private RateLimitInterface $repository
    ) {
    }

    public function isAllowed(string $identifier, int $limit, int $windowSeconds): RateLimitResult
    {
        $result = $this->repository->checkLimit($identifier, $limit, $windowSeconds);

        return new RateLimitResult(
            allowed: $result['allowed'],
            current: $result['current'],
            limit: $result['limit'],
            resetAt: $result['resetAt']
        );
    }
}
