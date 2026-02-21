<?php

declare(strict_types=1);

namespace app\domain\events;

use app\domain\values\BookStatus;

final readonly class BookUpdatedEvent implements DomainEvent
{
    public const string EVENT_TYPE = 'book.updated';

    public function __construct(
        public int $bookId,
        public int $oldYear,
        public int $newYear,
        public BookStatus $status,
    ) {
    }

    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'bookId' => $this->bookId,
            'oldYear' => $this->oldYear,
            'newYear' => $this->newYear,
            'status' => $this->status->value,
        ];
    }
}
