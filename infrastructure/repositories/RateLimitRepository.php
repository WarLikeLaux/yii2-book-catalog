<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\RateLimitInterface;
use yii\redis\Connection;

final readonly class RateLimitRepository implements RateLimitInterface
{
    public function __construct(private Connection $redis)
    {
    }

    /**
     * @return array{allowed: bool, current: int, limit: int, resetAt: int}
     */
    public function checkLimit(string $key, int $limit, int $windowSeconds): array
    {
        $now = time();
        $windowStart = $now - $windowSeconds;
        $resetAt = $now + $windowSeconds;
        $fullKey = 'ratelimit:' . $key;

        $this->redis->executeCommand('ZREMRANGEBYSCORE', [$fullKey, '-inf', (string)$windowStart]);
        $this->redis->executeCommand('ZADD', [$fullKey, (string)$now, (string)$now]);
        $current = (int)$this->redis->executeCommand('ZCARD', [$fullKey]);
        $this->redis->executeCommand('EXPIRE', [$fullKey, (string)($windowSeconds * 2)]);

        return [
            'allowed' => $current <= $limit,
            'current' => $current,
            'limit' => $limit,
            'resetAt' => $resetAt,
        ];
    }
}
