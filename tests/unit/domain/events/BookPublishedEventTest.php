<?php

declare(strict_types=1);

namespace tests\unit\domain\events;

use app\domain\events\BookPublishedEvent;
use Codeception\Test\Unit;

final class BookPublishedEventTest extends Unit
{
    public function testEventTypeAndPayload(): void
    {
        $event = new BookPublishedEvent(123, 'Published Book', 2024);

        $this->assertSame(123, $event->bookId);
        $this->assertSame('Published Book', $event->title);
        $this->assertSame(2024, $event->year);
        $this->assertSame(BookPublishedEvent::EVENT_TYPE, $event->getEventType());
        $this->assertSame([
            'bookId' => 123,
            'title' => 'Published Book',
            'year' => 2024,
        ], $event->getPayload());
    }
}
