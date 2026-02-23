<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\domain\events\BookStatusChangedEvent;
use app\domain\events\QueueableEvent;
use app\domain\values\BookStatus;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\adapters\EventSerializer;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;

final class EventToJobMapperTest extends Unit
{
    private EventToJobMapper $mapper;

    protected function _before(): void
    {
        $registry = new EventJobMappingRegistry(
            [BookStatusChangedEvent::class => NotifySubscribersJob::class],
            new EventSerializer(),
        );

        $this->mapper = new EventToJobMapper($registry);
    }

    public function testMapBookStatusChangedCreatesNotifySubscribersJob(): void
    {
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $job = $this->mapper->map($event);

        $this->assertInstanceOf(NotifySubscribersJob::class, $job);
        $this->assertSame(42, $job->bookId);
    }

    public function testMapUnknownEventReturnsNull(): void
    {
        $registry = new EventJobMappingRegistry([], new EventSerializer());
        $mapper = new EventToJobMapper($registry);
        $event = new class implements QueueableEvent {
            public function getEventType(): string
            {
                return 'unknown.event';
            }
        };

        $this->assertNull($mapper->map($event));
    }
}
