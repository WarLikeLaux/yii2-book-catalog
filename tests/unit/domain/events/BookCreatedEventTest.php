<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookCreatedEvent;
use Codeception\Test\Unit;

final class BookCreatedEventTest extends Unit
{
    public function testGettersAndPayload(): void
    {
        $event = new BookCreatedEvent(456, 'Domain Driven Design', 2024);

        $this->assertSame(456, $event->bookId);
        $this->assertSame('Domain Driven Design', $event->title);
        $this->assertSame(2024, $event->year);
        $this->assertSame(BookCreatedEvent::EVENT_TYPE, $event->getEventType());
        $this->assertSame([
            'bookId' => 456,
            'title' => 'Domain Driven Design',
            'year' => 2024,
        ], $event->getPayload());
    }
}
