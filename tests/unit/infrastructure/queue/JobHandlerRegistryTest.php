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
use yii\di\Container;
use yii\queue\Queue;

final class JobHandlerRegistryTest extends Unit
{
    public function testHandleDispatchesNotifySubscribersJob(): void
    {
        $handler = $this->createMock(NotifySubscribersHandler::class);
        $container = $this->createMock(Container::class);
        $queue = $this->createMock(Queue::class);
        $job = new NotifySubscribersJob(10, 'Title');

        $container->expects($this->once())
            ->method('get')
            ->with(NotifySubscribersHandler::class)
            ->willReturn($handler);

        $handler->expects($this->once())
            ->method('handle')
            ->with(10, 'Title', $queue);

        $registry = new JobHandlerRegistry($container);
        $registry->handle($job, $queue);
    }

    public function testHandleDispatchesNotifySingleSubscriberJob(): void
    {
        $handler = $this->createMock(NotifySingleSubscriberHandler::class);
        $container = $this->createMock(Container::class);
        $queue = $this->createMock(Queue::class);
        $job = new NotifySingleSubscriberJob('+7900', 'Message', 5);

        $container->expects($this->once())
            ->method('get')
            ->with(NotifySingleSubscriberHandler::class)
            ->willReturn($handler);

        $handler->expects($this->once())
            ->method('handle')
            ->with('+7900', 'Message', 5);

        $registry = new JobHandlerRegistry($container);
        $registry->handle($job, $queue);
    }

    public function testHandleThrowsOnUnsupportedJob(): void
    {
        $container = $this->createMock(Container::class);
        $queue = $this->createMock(Queue::class);
        $registry = new JobHandlerRegistry($container);

        $this->expectException(InvalidArgumentException::class);
        $registry->handle(new \stdClass(), $queue);
    }
}
