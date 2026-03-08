<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookStatusChangedEvent;
use app\domain\values\BookStatus;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\adapters\EventSerializer;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\adapters\EventToJobMapperInterface;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\queue\NotifySubscribersJob;
use PHPUnit\Framework\TestCase;

final class YiiEventPublisherAdapterTest extends TestCase
{
    private EventToJobMapperInterface $jobMapper;

    protected function setUp(): void
    {
        $registry = new EventJobMappingRegistry(
            [BookStatusChangedEvent::class => NotifySubscribersJob::class],
            new EventSerializer(),
        );
        $this->jobMapper = new EventToJobMapper($registry);
    }

    public function testPublishQueueableEventPushesToQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $queue->expects($this->once())
            ->method('push')
            ->with($this->callback(static fn(NotifySubscribersJob $job): bool => $job->bookId === 42));

        $adapter->publishEvent($event);
    }

    public function testPublishQueueableEventWithNullJobDoesNotPush(): void
    {
        $registry = new EventJobMappingRegistry(
            [BookStatusChangedEvent::class => static fn(): ?NotifySubscribersJob => null],
            new EventSerializer(),
        );
        $mapper = new EventToJobMapper($registry);

        $queue = $this->createMock(QueueInterface::class);
        $adapter = new YiiEventPublisherAdapter($queue, $mapper);
        $event = new BookStatusChangedEvent(42, BookStatus::Published, BookStatus::Draft, 2024);

        $queue->expects($this->never())->method('push');

        $adapter->publishEvent($event);
    }

    public function testPublishNonQueueableEventDoesNotPushToQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper);
        $event = new BookDeletedEvent(42, 2024, false);

        $queue->expects($this->never())->method('push');

        $adapter->publishEvent($event);
    }

    public function testPublishEventDispatchesToListeners(): void
    {
        $queue = $this->createStub(QueueInterface::class);
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookStatusChangedEvent::class]);
        $listener->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(BookStatusChangedEvent::class));

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $adapter->publishEvent($event);
    }

    public function testPublishEventSkipsUnsubscribedListeners(): void
    {
        $queue = $this->createStub(QueueInterface::class);
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookDeletedEvent::class]);
        $listener->expects($this->never())->method('handle');

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $adapter->publishEvent($event);
    }
}
