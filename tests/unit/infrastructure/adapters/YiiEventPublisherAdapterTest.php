<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookPublishedEvent;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\adapters\EventToJobMapperInterface;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class YiiEventPublisherAdapterTest extends Unit
{
    private QueueInterface&MockObject $queue;
    private EventToJobMapperInterface $jobMapper;

    protected function _before(): void
    {
        $this->queue = $this->createMock(QueueInterface::class);

        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => static fn(BookPublishedEvent $e): NotifySubscribersJob => new NotifySubscribersJob(
                bookId: $e->bookId,
                title: $e->title,
            ),
        ]);
        $this->jobMapper = new EventToJobMapper($registry);
    }

    public function testPublishQueueableEventPushesToQueue(): void
    {
        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(static fn (NotifySubscribersJob $job) => $job->bookId === 42
                && $job->title === 'Test Book'));

        $adapter->publishEvent($event);
    }

    public function testPublishNonQueueableEventDoesNotPushToQueue(): void
    {
        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper);
        $event = new BookDeletedEvent(42, 2024, false);

        $this->queue->expects($this->never())->method('push');

        $adapter->publishEvent($event);
    }

    public function testPublishEventDispatchesToListeners(): void
    {
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookPublishedEvent::class]);
        $listener->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(BookPublishedEvent::class));

        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper, $listener);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $adapter->publishEvent($event);
    }

    public function testPublishEventSkipsUnsubscribedListeners(): void
    {
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookDeletedEvent::class]);
        $listener->expects($this->never())->method('handle');

        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper, $listener);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $adapter->publishEvent($event);
    }
}
