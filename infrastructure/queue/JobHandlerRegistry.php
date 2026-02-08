<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use InvalidArgumentException;
use yii\di\Container;
use yii\queue\Queue;

/**
 * NOTE: Реализует паттерн Command Bus для обхода ограничений сериализации Yii Queue.
 * @see docs/DECISIONS.md (см. пункт "6. DI в фоновых задачах")
 */
final readonly class JobHandlerRegistry
{
    public function __construct(
        private Container $container,
    ) {
    }

    public function handle(object $job, Queue $queue): void
    {
        if ($job instanceof NotifySubscribersJob) {
            $this->container->get(NotifySubscribersHandler::class)->handle(
                $job->bookId,
                $queue,
            );
            return;
        }

        if ($job instanceof NotifySingleSubscriberJob) {
            $this->container->get(NotifySingleSubscriberHandler::class)->handle(
                $job->phone,
                $job->message,
                $job->bookId,
            );
            return;
        }

        throw new InvalidArgumentException('Unsupported job type: ' . $job::class);
    }
}
