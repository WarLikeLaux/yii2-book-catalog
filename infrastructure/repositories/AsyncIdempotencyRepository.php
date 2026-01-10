<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\infrastructure\persistence\AsyncIdempotencyLog;
use Throwable;

final readonly class AsyncIdempotencyRepository implements AsyncIdempotencyStorageInterface
{
    public function acquire(string $key): bool
    {
        $model = new AsyncIdempotencyLog();
        $model->idempotency_key = $key;
        $model->created_at = time();

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
        $threshold = time() - $maxAgeSeconds;

        return AsyncIdempotencyLog::deleteAll(['<', 'created_at', $threshold]);
    }
}
