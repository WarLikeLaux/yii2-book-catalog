<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\domain\events\BookPublishedEvent;
use app\domain\events\QueueableEvent;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\adapters\EventToJobMapper;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use InvalidArgumentException;

final class EventToJobMapperTest extends Unit
{
    private EventToJobMapper $mapper;

    protected function _before(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => NotifySubscribersJob::class,
        ]);

        $this->mapper = new EventToJobMapper($registry);
    }

    public function testMapBookPublishedEventCreatesNotifySubscribersJob(): void
    {
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $this->mapper->map($event);

        $this->assertInstanceOf(NotifySubscribersJob::class, $job);
        $this->assertSame(42, $job->bookId);
        $this->assertSame('Test Book', $job->title);
    }

    public function testMapUnknownEventThrowsException(): void
    {
        $registry = new EventJobMappingRegistry([]);
        $mapper = new EventToJobMapper($registry);
        $event = new class implements QueueableEvent {
            public function getEventType(): string
            {
                return 'unknown.event';
            }

            /** @return array<string, mixed> */
            public function getPayload(): array
            {
                return [];
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No job mapping for event:');

        $mapper->map($event);
    }
}
