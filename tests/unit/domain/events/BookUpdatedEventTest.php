<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookUpdatedEvent;
use Codeception\Test\Unit;

final class BookUpdatedEventTest extends Unit
{
    public function testEventTypeAndPayload(): void
    {
        $event = new BookUpdatedEvent(123, 2023, 2024, true);

        $this->assertSame(123, $event->bookId);
        $this->assertSame(2023, $event->oldYear);
        $this->assertSame(2024, $event->newYear);
        $this->assertTrue($event->isPublished);
        $this->assertSame(BookUpdatedEvent::EVENT_TYPE, $event->getEventType());
        $this->assertSame([
            'bookId' => 123,
            'oldYear' => 2023,
            'newYear' => 2024,
            'isPublished' => true,
        ], $event->getPayload());
    }

    public function testUnpublishedBook(): void
    {
        $event = new BookUpdatedEvent(456, 2024, 2024, false);

        $this->assertFalse($event->isPublished);
        $this->assertSame([
            'bookId' => 456,
            'oldYear' => 2024,
            'newYear' => 2024,
            'isPublished' => false,
        ], $event->getPayload());
    }
}
