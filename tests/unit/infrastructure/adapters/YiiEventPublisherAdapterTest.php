<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookStatusChangedEvent;
use app\domain\values\BookStatus;
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
            BookStatusChangedEvent::class => NotifySubscribersJob::class,
        ]);
        $this->jobMapper = new EventToJobMapper($registry);
    }

    public function testPublishQueueableEventPushesToQueue(): void
    {
        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(static fn(NotifySubscribersJob $job): bool => $job->bookId === 42));

        $adapter->publishEvent($event);
    }

    public function testPublishQueueableEventWithNullJobDoesNotPush(): void
    {
        $registry = new EventJobMappingRegistry([
            BookStatusChangedEvent::class => static fn(): ?NotifySubscribersJob => null,
        ]);
        $mapper = new EventToJobMapper($registry);

        $adapter = new YiiEventPublisherAdapter($this->queue, $mapper);
        $event = new BookStatusChangedEvent(42, BookStatus::Published, BookStatus::Draft);

        $this->queue->expects($this->never())->method('push');

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
        $listener->method('subscribedEvents')->willReturn([BookStatusChangedEvent::class]);
        $listener->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(BookStatusChangedEvent::class));

        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published);

        $adapter->publishEvent($event);
    }

    public function testPublishEventSkipsUnsubscribedListeners(): void
    {
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookDeletedEvent::class]);
        $listener->expects($this->never())->method('handle');

        $adapter = new YiiEventPublisherAdapter($this->queue, $this->jobMapper, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published);

        $adapter->publishEvent($event);
    }
}
