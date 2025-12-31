<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookCreatedEvent;
use app\domain\events\BookPublishedEvent;
use app\domain\events\QueueableEvent;
use app\infrastructure\adapters\YiiEventPublisherAdapter;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class YiiEventPublisherAdapterTest extends Unit
{
    private QueueInterface&MockObject $queue;

    protected function _before(): void
    {
        $this->queue = $this->createMock(QueueInterface::class);
    }

    public function testPublishQueueableEventPushesToQueue(): void
    {
        $adapter = new YiiEventPublisherAdapter($this->queue);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(fn (NotifySubscribersJob $job) => $job->bookId === 42
                && $job->title === 'Test Book'));

        $adapter->publishEvent($event);
    }

    public function testPublishNonQueueableEventDoesNotPushToQueue(): void
    {
        $adapter = new YiiEventPublisherAdapter($this->queue);
        $event = new BookCreatedEvent(42, 'Test Book', 2024);

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

        $adapter = new YiiEventPublisherAdapter($this->queue, $listener);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $adapter->publishEvent($event);
    }

    public function testPublishEventSkipsUnsubscribedListeners(): void
    {
        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('subscribedEvents')->willReturn([BookCreatedEvent::class]);
        $listener->expects($this->never())->method('handle');

        $adapter = new YiiEventPublisherAdapter($this->queue, $listener);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $adapter->publishEvent($event);
    }

    public function testPublishCustomQueueableEventCreatesCorrectJob(): void
    {
        $event = new class implements QueueableEvent {
            public function getEventType(): string
            {
                return 'test.event';
            }

            /** @return array<string, mixed> */
            public function getPayload(): array
            {
                return ['id' => 1];
            }

            public function getJobClass(): string
            {
                return NotifySubscribersJob::class;
            }

            /** @return array<string, mixed> */
            public function getJobPayload(): array
            {
                return ['bookId' => 99, 'title' => 'Custom Event Book'];
            }
        };

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(fn (NotifySubscribersJob $job) => $job->bookId === 99
                && $job->title === 'Custom Event Book'));

        $adapter = new YiiEventPublisherAdapter($this->queue);
        $adapter->publishEvent($event);
    }
}
