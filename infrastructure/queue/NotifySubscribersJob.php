<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use RuntimeException;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

final readonly class NotifySubscribersJob implements JobInterface, RetryableJobInterface
{
    private const int TTR_SECONDS = 300;

    public function __construct(
        public int $bookId,
        public string $title,
    ) {
    }

    /** @codeCoverageIgnore Fan-out джоба: зависит от очереди и внешних сервисов */
    public function execute($queue): void
    {
        $this->getRegistry($queue)->handle($this, $queue);
    }

    public function getTtr(): int
    {
        return self::TTR_SECONDS;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }

    private function getRegistry(mixed $queue): JobHandlerRegistry
    {
        if (!$queue instanceof HandlerAwareQueue) {
            throw new RuntimeException('Queue must be HandlerAwareQueue.');
        }

        return $queue->getJobHandlerRegistry();
    }
}
