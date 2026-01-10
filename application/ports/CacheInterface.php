<?php

declare(strict_types=1);

namespace app\application\ports;

interface CacheInterface
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function getOrSet(string $key, callable $callback, int $ttl = 3600): mixed;

    public function delete(string $key): void;

    public function deleteByPrefix(string $prefix): void;
}
