<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\common\dto\RateLimitResult;
use app\application\ports\RateLimitInterface;
use app\application\ports\TracerInterface;

final readonly class RateLimitRepositoryTracingDecorator implements RateLimitInterface
{
    public function __construct(
        private RateLimitInterface $decorated,
        private TracerInterface $tracer,
    ) {
    }

    public function checkLimit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        return $this->tracer->trace(
            'RateLimitRepository::checkLimit',
            fn(): RateLimitResult => $this->decorated->checkLimit($key, $limit, $windowSeconds),
        );
    }
}
