<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\common\dto\RateLimitResult;
use app\application\ports\RateLimitInterface;
use Psr\Clock\ClockInterface;
use yii\redis\Connection;

final readonly class RateLimitRepository implements RateLimitInterface
{
    public function __construct(
        private Connection $redis,
        private ClockInterface $clock,
    ) {
    }

    public function checkLimit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        $dateTime = $this->clock->now();
        $now = $dateTime->getTimestamp();
        $windowStart = $now - $windowSeconds;
        $resetAt = $now + $windowSeconds;
        $fullKey = 'ratelimit:' . $key;

        $this->redis->executeCommand('ZREMRANGEBYSCORE', [$fullKey, '-inf', (string)$windowStart]);
        $microtime = (float)$dateTime->format('U.u');
        $uniqueMember = sprintf('%.6f_%s', $microtime, bin2hex(random_bytes(4)));
        $this->redis->executeCommand('ZADD', [$fullKey, (string)$microtime, $uniqueMember]);
        $current = (int)$this->redis->executeCommand('ZCARD', [$fullKey]);
        $this->redis->executeCommand('EXPIRE', [$fullKey, (string)($windowSeconds * 2)]);

        return new RateLimitResult(
            allowed: $current <= $limit,
            current: $current,
            limit: $limit,
            resetAt: $resetAt,
        );
    }
}
