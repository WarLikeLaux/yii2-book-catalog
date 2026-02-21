<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\common\dto\IdempotencyRecordDto;
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

    #[Override]
    public function getRecord(string $key): IdempotencyRecordDto|null
    {
        return $this->tracer->trace(
            'IdempotencyRepo::' . __FUNCTION__,
            fn(): IdempotencyRecordDto|null => $this->repository->getRecord($key),
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
