<?php

declare(strict_types=1);

namespace app\domain\events;

final readonly class BookCreatedEvent implements DomainEvent
{
    public function __construct(
        public int $bookId,
        public string $title
    ) {
    }

    public function getEventType(): string
    {
        return 'book.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'bookId' => $this->bookId,
            'title' => $this->title,
        ];
    }
}
