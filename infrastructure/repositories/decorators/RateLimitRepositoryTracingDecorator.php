<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\RateLimitInterface;
use app\application\ports\TracerInterface;

final readonly class RateLimitRepositoryTracingDecorator implements RateLimitInterface
{
    public function __construct(
        private RateLimitInterface $decorated,
        private TracerInterface $tracer,
    ) {
    }

    public function checkLimit(string $key, int $limit, int $windowSeconds): array
    {
        return $this->tracer->trace(
            'RateLimitRepository::checkLimit',
            fn(): array => $this->decorated->checkLimit($key, $limit, $windowSeconds),
        );
    }
}
