<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookUpdatedEvent;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookUpdatedEventTest extends Unit
{
    public function testEventTypeAndProperties(): void
    {
        $event = new BookUpdatedEvent(123, 2023, 2024, BookStatus::Published);

        $this->assertSame(123, $event->bookId);
        $this->assertSame(2023, $event->oldYear);
        $this->assertSame(2024, $event->newYear);
        $this->assertSame(BookStatus::Published, $event->status);
        $this->assertSame(BookUpdatedEvent::EVENT_TYPE, $event->getEventType());
    }

    public function testDraftBook(): void
    {
        $event = new BookUpdatedEvent(456, 2024, 2024, BookStatus::Draft);

        $this->assertSame(BookStatus::Draft, $event->status);
    }
}
