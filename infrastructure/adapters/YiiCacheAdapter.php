<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\CacheInterface;
use yii\caching\CacheInterface as YiiCache;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection;

final readonly class YiiCacheAdapter implements CacheInterface
{
    public function __construct(
        private YiiCache $cache
    ) {
    }

    public function getOrSet(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->cache->getOrSet($key, $callback, $ttl);
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function deleteByPrefix(string $prefix): void
    {
        if (!$this->cache instanceof RedisCache) {
            return;
        }

        $redis = $this->cache->redis;
        if (!$redis instanceof Connection) {
            return;
        }

        $cursor = 0;
        $fullPrefix = $this->cache->keyPrefix . $prefix;

        do {
            /** @var array{0: string, 1: array<string>} $result */
            $result = $redis->executeCommand('SCAN', [$cursor, 'MATCH', $fullPrefix . '*', 'COUNT', 100]);
            $cursor = (int)$result[0];
            $keys = $result[1];

            if ($keys === []) {
                continue;
            }

            $redis->executeCommand('DEL', $keys);
        } while ($cursor !== 0);
    }
}
