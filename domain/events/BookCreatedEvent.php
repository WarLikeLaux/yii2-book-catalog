<?php

declare(strict_types=1);

namespace app\domain\events;

final readonly class BookCreatedEvent implements DomainEvent
{
    public const string EVENT_TYPE = 'book.created';

    public function __construct(
        public int $bookId,
        public string $title,
        public int $year
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
            'title' => $this->title,
            'year' => $this->year,
        ];
    }
}
