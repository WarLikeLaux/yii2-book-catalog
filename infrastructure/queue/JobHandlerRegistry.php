<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use InvalidArgumentException;
use yii\queue\Queue;

final readonly class JobHandlerRegistry
{
    public function __construct(
        private NotifySubscribersHandler $notifySubscribersHandler,
        private NotifySingleSubscriberHandler $notifySingleSubscriberHandler
    ) {
    }

    public function handle(object $job, Queue $queue): void
    {
        if ($job instanceof NotifySubscribersJob) {
            $this->notifySubscribersHandler->handle($job->bookId, $job->title, $queue);
            return;
        }

        if ($job instanceof NotifySingleSubscriberJob) {
            $this->notifySingleSubscriberHandler->handle($job->phone, $job->message, $job->bookId);
            return;
        }

        throw new InvalidArgumentException('Unsupported job type: ' . $job::class);
    }
}
