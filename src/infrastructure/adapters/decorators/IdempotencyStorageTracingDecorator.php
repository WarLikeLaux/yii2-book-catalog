<?php

declare(strict_types=1);

namespace app\infrastructure\adapters\decorators;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\ports\IdempotencyInterface;
use app\application\ports\TracerInterface;
use Override;

final readonly class IdempotencyStorageTracingDecorator implements IdempotencyInterface
{
    public function __construct(
        private IdempotencyInterface $storage,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function getRecord(string $key): IdempotencyRecordDto|null
    {
        return $this->tracer->trace(
            'IdempotencyStorage::' . __FUNCTION__,
            fn(): IdempotencyRecordDto|null => $this->storage->getRecord($key),
        );
    }

    #[Override]
    public function saveStarted(string $key, int $ttl): bool
    {
        return $this->tracer->trace(
            'IdempotencyStorage::' . __FUNCTION__,
            fn(): bool => $this->storage->saveStarted($key, $ttl),
        );
    }

    #[Override]
    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void
    {
        $this->tracer->trace(
            'IdempotencyStorage::' . __FUNCTION__,
            fn() => $this->storage->saveResponse($key, $statusCode, $body, $ttl),
        );
    }
}
