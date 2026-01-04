<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use InvalidArgumentException;
use yii\queue\Queue;

final class JobHandlerRegistry
{
    public function handle(object $job, Queue $queue): void
    {
        if ($job instanceof NotifySubscribersJob) {
            \Yii::$container->get(NotifySubscribersHandler::class)
                ->handle($job->bookId, $job->title, $queue);
            return;
        }

        if ($job instanceof NotifySingleSubscriberJob) {
            \Yii::$container->get(NotifySingleSubscriberHandler::class)
                ->handle($job->phone, $job->message, $job->bookId);
            return;
        }

        throw new InvalidArgumentException('Unsupported job type: ' . $job::class);
    }
}
