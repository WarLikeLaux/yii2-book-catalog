<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\IdempotencyInterface;
use app\application\ports\TracerInterface;
use Override;

final readonly class IdempotencyRepositoryTracingDecorator implements IdempotencyInterface
{
    public function __construct(
        private IdempotencyInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    /** @return array{status: string, status_code: int|null, body: string|null}|null */
    #[Override]
    public function getRecord(string $key): array|null
    {
        return $this->tracer->trace(
            'IdempotencyRepo::' . __FUNCTION__,
            fn(): array|null => $this->repository->getRecord($key),
        );
    }

    #[Override]
    public function saveStarted(string $key, int $ttl): bool
    {
        return $this->tracer->trace(
            'IdempotencyRepo::' . __FUNCTION__,
            fn(): bool => $this->repository->saveStarted($key, $ttl),
        );
    }

    #[Override]
    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void
    {
        $this->tracer->trace(
            'IdempotencyRepo::' . __FUNCTION__,
            fn() => $this->repository->saveResponse($key, $statusCode, $body, $ttl),
        );
    }
}
