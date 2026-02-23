<?php

declare(strict_types=1);

namespace app\domain\events;

use app\domain\values\BookStatus;

final readonly class BookStatusChangedEvent implements QueueableEvent
{
    public const string EVENT_TYPE = 'book.status_changed';

    public function __construct(
        public int $bookId,
        public BookStatus $oldStatus,
        public BookStatus $newStatus,
        public int $year,
    ) {
    }

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }
}
