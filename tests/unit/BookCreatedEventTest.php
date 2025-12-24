<?php

declare(strict_types=1);

namespace tests\unit;

use app\domain\events\BookCreatedEvent;
use Codeception\Test\Unit;

final class BookCreatedEventTest extends Unit
{
    public function testGettersAndPayload(): void
    {
        $event = new BookCreatedEvent(456, 'Domain Driven Design');

        $this->assertSame(456, $event->bookId);
        $this->assertSame('Domain Driven Design', $event->title);
        $this->assertSame('book.created', $event->getEventType());
        $this->assertSame([
            'bookId' => 456,
            'title' => 'Domain Driven Design',
        ], $event->getPayload());
    }
}
