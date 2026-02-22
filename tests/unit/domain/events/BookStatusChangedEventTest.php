<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookStatusChangedEvent;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookStatusChangedEventTest extends Unit
{
    public function testEventTypeAndProperties(): void
    {
        $event = new BookStatusChangedEvent(42, BookStatus::Draft, BookStatus::Published, 2023);

        $this->assertSame(42, $event->bookId);
        $this->assertSame(BookStatus::Draft, $event->oldStatus);
        $this->assertSame(BookStatus::Published, $event->newStatus);
        $this->assertSame(2023, $event->year);
        $this->assertSame(BookStatusChangedEvent::EVENT_TYPE, $event->getEventType());
    }
}
