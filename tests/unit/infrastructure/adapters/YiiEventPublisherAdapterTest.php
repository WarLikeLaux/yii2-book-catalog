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
use Psr\Log\LoggerInterface;
use RuntimeException;

final class YiiEventPublisherAdapterTest extends TestCase
{
    private EventToJobMapperInterface $jobMapper;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $registry = new EventJobMappingRegistry(
            [BookStatusChangedEvent::class => NotifySubscribersJob::class],
            new EventSerializer(),
        );
        $this->jobMapper = new EventToJobMapper($registry);
        $this->logger = $this->createStub(LoggerInterface::class);
    }

    public function testPublishQueueableEventPushesToQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $this->logger);
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
        $adapter = new YiiEventPublisherAdapter($queue, $mapper, $this->logger);
        $event = new BookStatusChangedEvent(42, BookStatus::Published, BookStatus::Draft, 2024);

        $queue->expects($this->never())->method('push');

        $adapter->publishEvent($event);
    }

    public function testPublishNonQueueableEventDoesNotPushToQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $this->logger);
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

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $this->logger, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $adapter->publishEvent($event);
    }

    public function testPublishEventSkipsUnsubscribedListeners(): void
    {
        $queue = $this->createStub(QueueInterface::class);
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookDeletedEvent::class]);
        $listener->expects($this->never())->method('handle');

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $this->logger, $listener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $adapter->publishEvent($event);
    }

    public function testListenerExceptionDoesNotPreventQueueDispatch(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $failingListener = $this->createMock(EventListenerInterface::class);
        $failingListener->method('subscribedEvents')->willReturn([BookStatusChangedEvent::class]);
        $failingListener->method('handle')->willThrowException(new RuntimeException('Redis down'));

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $logger, $failingListener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $logger->expects($this->once())
            ->method('error')
            ->with('Redis down', $this->callback(
                static fn(array $ctx): bool => $ctx['listener'] === $failingListener::class
                    && $ctx['event'] === BookStatusChangedEvent::class,
            ));

        $queue->expects($this->once())->method('push');

        $adapter->publishEvent($event);
    }

    public function testListenerExceptionDoesNotPreventOtherListeners(): void
    {
        $queue = $this->createStub(QueueInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $failingListener = $this->createMock(EventListenerInterface::class);
        $failingListener->method('subscribedEvents')->willReturn([BookStatusChangedEvent::class]);
        $failingListener->method('handle')->willThrowException(new RuntimeException('fail'));

        $successListener = $this->createMock(EventListenerInterface::class);
        $successListener->method('subscribedEvents')->willReturn([BookStatusChangedEvent::class]);
        $successListener->expects($this->once())->method('handle');

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $logger, $failingListener, $successListener);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $adapter->publishEvent($event);
    }

    public function testQueueExceptionIsLoggedAndSwallowed(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $queue->method('push')->willThrowException(new RuntimeException('Queue unavailable'));

        $adapter = new YiiEventPublisherAdapter($queue, $this->jobMapper, $logger);
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $logger->expects($this->once())
            ->method('error')
            ->with('Queue unavailable', $this->callback(
                static fn(array $ctx): bool => $ctx['job'] === NotifySubscribersJob::class
                    && $ctx['event'] === BookStatusChangedEvent::class,
            ));

        $adapter->publishEvent($event);
    }
}
