<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookUpdatedEvent;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookUpdatedEventTest extends Unit
{
    public function testEventTypeAndPayload(): void
    {
        $event = new BookUpdatedEvent(123, 2023, 2024, BookStatus::Published);

        $this->assertSame(123, $event->bookId);
        $this->assertSame(2023, $event->oldYear);
        $this->assertSame(2024, $event->newYear);
        $this->assertSame(BookStatus::Published, $event->status);
        $this->assertSame(BookUpdatedEvent::EVENT_TYPE, $event->getEventType());
        $this->assertSame([
            'bookId' => 123,
            'oldYear' => 2023,
            'newYear' => 2024,
            'status' => BookStatus::Published->value,
        ], $event->getPayload());
    }

    public function testDraftBook(): void
    {
        $event = new BookUpdatedEvent(456, 2024, 2024, BookStatus::Draft);

        $this->assertSame(BookStatus::Draft, $event->status);
        $this->assertSame([
            'bookId' => 456,
            'oldYear' => 2024,
            'newYear' => 2024,
            'status' => BookStatus::Draft->value,
        ], $event->getPayload());
    }
}
