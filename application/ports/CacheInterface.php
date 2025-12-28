<?php

declare(strict_types=1);

namespace app\application\ports;

interface CacheInterface
{
    /**
     * Get cached value or compute and store it.
     *
     * @template T
     * @param string $key cache key
     * @param callable(): T $callback function to compute value if not cached
     * @param int $ttl time to live in seconds
     * @return T
     */
    public function getOrSet(string $key, callable $callback, int $ttl = 3600): mixed;

    public function delete(string $key): void;

    public function deleteByPrefix(string $prefix): void;
}
