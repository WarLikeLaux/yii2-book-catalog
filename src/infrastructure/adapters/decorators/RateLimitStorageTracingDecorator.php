<?php

declare(strict_types=1);

namespace app\infrastructure\adapters\decorators;

use app\application\common\dto\RateLimitResult;
use app\application\ports\RateLimitInterface;
use app\application\ports\TracerInterface;
use Override;

final readonly class RateLimitStorageTracingDecorator implements RateLimitInterface
{
    public function __construct(
        private RateLimitInterface $decorated,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function checkLimit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        return $this->tracer->trace(
            'RateLimitStorage::checkLimit',
            fn(): RateLimitResult => $this->decorated->checkLimit($key, $limit, $windowSeconds),
        );
    }
}
