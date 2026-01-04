<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use RuntimeException;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

final class NotifySingleSubscriberJob implements JobInterface, RetryableJobInterface
{
    private const int TTR_SECONDS = 30;

    public function __construct(
        public string $phone,
        public string $message,
        public int $bookId,
    ) {
    }

    public function execute($queue): void
    {
        $this->getRegistry($queue)->handle($this, $queue);
    }

    public function getTtr(): int
    {
        return self::TTR_SECONDS;
    }

    public function canRetry($attempt, $_error): bool
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
