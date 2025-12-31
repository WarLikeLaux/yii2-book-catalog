<?php

declare(strict_types=1);

namespace app\domain\events;

final readonly class BookDeletedEvent implements DomainEvent
{
    public function __construct(
        public int $bookId,
        public int $year,
        public bool $wasPublished
    ) {
    }

    public function getEventType(): string
    {
        return 'book.deleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'bookId' => $this->bookId,
            'year' => $this->year,
            'wasPublished' => $this->wasPublished,
        ];
    }
}
