<?php

declare(strict_types=1);

namespace app\domain\events;

final readonly class BookCreatedEvent
{
    public function __construct(
        public int $bookId,
        public string $title
    ) {
    }
}
