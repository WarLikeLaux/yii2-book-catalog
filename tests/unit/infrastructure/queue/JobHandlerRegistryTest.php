<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queue;

use app\infrastructure\queue\handlers\NotifySingleSubscriberHandler;
use app\infrastructure\queue\handlers\NotifySubscribersHandler;
use app\infrastructure\queue\JobHandlerRegistry;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Yii;
use yii\queue\Queue;

final class JobHandlerRegistryTest extends Unit
{
    public function testHandleDispatchesNotifySubscribersJob(): void
    {
        $notifySubscribersHandler = $this->createMock(NotifySubscribersHandler::class);
        $queue = $this->createMock(Queue::class);

        $notifySubscribersHandler->expects($this->once())
            ->method('handle')
            ->with(10, 'Title', $queue);

        Yii::$container->set(NotifySubscribersHandler::class, $notifySubscribersHandler);

        $registry = new JobHandlerRegistry();
        $registry->handle(new NotifySubscribersJob(10, 'Title'), $queue);
    }

    public function testHandleDispatchesNotifySingleSubscriberJob(): void
    {
        $notifySingleSubscriberHandler = $this->createMock(NotifySingleSubscriberHandler::class);
        $queue = $this->createMock(Queue::class);

        $notifySingleSubscriberHandler->expects($this->once())
            ->method('handle')
            ->with('+7900', 'Message', 5);

        Yii::$container->set(NotifySingleSubscriberHandler::class, $notifySingleSubscriberHandler);

        $registry = new JobHandlerRegistry();
        $registry->handle(new NotifySingleSubscriberJob('+7900', 'Message', 5), $queue);
    }

    public function testHandleThrowsOnUnsupportedJob(): void
    {
        $queue = $this->createMock(Queue::class);

        $registry = new JobHandlerRegistry();

        $this->expectException(InvalidArgumentException::class);
        $registry->handle(new \stdClass(), $queue);
    }
}
