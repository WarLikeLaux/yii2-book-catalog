<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookDeletedEvent;
use Codeception\Test\Unit;

final class BookDeletedEventTest extends Unit
{
    public function testEventTypeAndPayload(): void
    {
        $event = new BookDeletedEvent(123, 2024, true);

        $this->assertSame(123, $event->bookId);
        $this->assertSame(2024, $event->year);
        $this->assertTrue($event->wasPublished);
        $this->assertSame(BookDeletedEvent::EVENT_TYPE, $event->getEventType());
        $this->assertSame([
            'bookId' => 123,
            'year' => 2024,
            'wasPublished' => true,
        ], $event->getPayload());
    }

    public function testUnpublishedBook(): void
    {
        $event = new BookDeletedEvent(456, 2023, false);

        $this->assertFalse($event->wasPublished);
        $this->assertSame([
            'bookId' => 456,
            'year' => 2023,
            'wasPublished' => false,
        ], $event->getPayload());
    }
}
