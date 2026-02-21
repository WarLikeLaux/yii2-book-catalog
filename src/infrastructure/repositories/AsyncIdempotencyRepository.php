<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\infrastructure\persistence\AsyncIdempotencyLog;
use Psr\Clock\ClockInterface;
use Throwable;

final readonly class AsyncIdempotencyRepository implements AsyncIdempotencyStorageInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function acquire(string $key): bool
    {
        $model = new AsyncIdempotencyLog();
        $model->idempotency_key = $key;
        $model->created_at = $this->clock->now()->getTimestamp();

        try {
            return $model->save();
        } catch (Throwable) { // @codeCoverageIgnore
            return false; // @codeCoverageIgnore
        }
    }

    public function release(string $key): void
    {
        AsyncIdempotencyLog::deleteAll(['idempotency_key' => $key]);
    }

    public function deleteExpired(int $maxAgeSeconds): int
    {
        $threshold = $this->clock->now()->getTimestamp() - $maxAgeSeconds;

        return AsyncIdempotencyLog::deleteAll(['<', 'created_at', $threshold]);
    }
}
