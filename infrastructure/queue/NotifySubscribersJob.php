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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue): void
    {
        $this->getRegistry($queue)->handle($this, $queue);
    }

    public function getTtr(): int
    {
        return self::TTR_SECONDS;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param int $attempt
     * @param \Throwable $_error
     */
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
