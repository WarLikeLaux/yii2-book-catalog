<?php

declare(strict_types=1);

namespace app\application\ports;

interface AsyncIdempotencyStorageInterface
{
    /**
 * Attempt to acquire an idempotency lock for the given key.
 *
 * @param string $key The idempotency key to acquire.
 * @return bool `true` if the lock was acquired, `false` otherwise.
 */
public function acquire(string $key): bool;

    /**
 * Releases the idempotency lock associated with the given key.
 *
 * @param string $key The idempotency key or lock identifier to release.
 */
public function release(string $key): void;

    /**
 * Removes idempotency entries older than the given maximum age.
 *
 * Deletes stored idempotency records whose age is greater than `$maxAgeSeconds` and returns how many entries were removed.
 *
 * @param int $maxAgeSeconds Maximum allowed age in seconds; entries older than this value will be deleted.
 * @return int The number of entries deleted.
 */
public function deleteExpired(int $maxAgeSeconds): int;
}