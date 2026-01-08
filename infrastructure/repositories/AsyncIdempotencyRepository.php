<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\infrastructure\persistence\AsyncIdempotencyLog;
use Throwable;

final readonly class AsyncIdempotencyRepository implements AsyncIdempotencyStorageInterface
{
    /**
     * Attempts to create an idempotency record for the given key to acquire a lock.
     *
     * @param string $key The idempotency key to acquire.
     * @return bool `true` if the record was created and the lock acquired, `false` if creation failed or an error occurred.
     */
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

    /**
     * Removes all idempotency records for the given key.
     *
     * @param string $key The idempotency key whose records should be deleted.
     */
    public function release(string $key): void
    {
        AsyncIdempotencyLog::deleteAll(['idempotency_key' => $key]);
    }

    /**
     * Deletes idempotency records older than the specified age.
     *
     * @param int $maxAgeSeconds The maximum allowed age in seconds; records with a creation timestamp older than this will be removed.
     * @return int The number of records deleted.
     */
    public function deleteExpired(int $maxAgeSeconds): int
    {
        $threshold = time() - $maxAgeSeconds;

        return AsyncIdempotencyLog::deleteAll(['<', 'created_at', $threshold]);
    }
}