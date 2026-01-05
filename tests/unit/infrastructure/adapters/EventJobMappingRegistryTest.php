<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\domain\events\BookPublishedEvent;
use app\domain\events\QueueableEvent;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use InvalidArgumentException;

final class EventJobMappingRegistryTest extends Unit
{
    public function testResolveReturnsJobForRegisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => static fn(BookPublishedEvent $e): NotifySubscribersJob => new NotifySubscribersJob(
                bookId: $e->bookId,
                title: $e->title,
            ),
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $registry->resolve($event);

        $this->assertInstanceOf(NotifySubscribersJob::class, $job);
        $this->assertSame(42, $job->bookId);
        $this->assertSame('Test Book', $job->title);
    }

    public function testResolveThrowsExceptionForUnregisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([]);
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

        $registry->resolve($event);
    }

    public function testHasReturnsTrueForRegisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => static fn(BookPublishedEvent $e): NotifySubscribersJob => new NotifySubscribersJob(
                bookId: $e->bookId,
                title: $e->title,
            ),
        ]);

        $this->assertTrue($registry->has(BookPublishedEvent::class));
    }

    public function testHasReturnsFalseForUnregisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([]);

        $this->assertFalse($registry->has(BookPublishedEvent::class));
    }
}
