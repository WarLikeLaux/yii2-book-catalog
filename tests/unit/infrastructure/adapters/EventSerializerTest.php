<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\domain\events\BookStatusChangedEvent;
use app\domain\events\QueueableEvent;
use app\domain\values\BookStatus;
use app\infrastructure\adapters\EventSerializer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EventSerializerTest extends TestCase
{
    private EventSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new EventSerializer();
    }

    public function testSerializeBookStatusChangedEvent(): void
    {
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2024);

        $payload = $this->serializer->serialize($event);

        $this->assertSame([
            'bookId' => 42,
            'oldStatus' => BookStatus::Draft->value,
            'newStatus' => BookStatus::Published->value,
            'year' => 2024,
        ], $payload);
    }

    public function testSerializeUnknownEventThrows(): void
    {
        $event = new class implements QueueableEvent {
            public function getEventType(): string
            {
                return 'unknown.event';
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown queueable event:');

        $this->serializer->serialize($event);
    }
}
