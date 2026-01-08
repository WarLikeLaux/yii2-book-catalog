<?php

declare(strict_types=1);

namespace app\application\ports;

interface CacheInterface
{
    /**
 * Retrieve the value for a cache key or compute, store, and return it when absent.
 *
 * The provided callback is invoked only if the key is not present; its result is stored
 * in the cache with the given time-to-live.
 *
 * @template T
 * @param string $key The cache key.
 * @param callable(): T $callback Callback that produces the value if the key is missing.
 * @param int $ttl Time-to-live in seconds for the stored value (default 3600).
 * @return T The cached or newly computed value.
 */
    public function getOrSet(string $key, callable $callback, int $ttl = 3600): mixed;

    public function delete(string $key): void;

    public function deleteByPrefix(string $prefix): void;
}