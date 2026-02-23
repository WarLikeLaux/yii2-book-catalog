<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\BookStatusChangedEvent;
use app\domain\events\QueueableEvent;
use InvalidArgumentException;

final readonly class EventSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(QueueableEvent $event): array
    {
        if ($event instanceof BookStatusChangedEvent) {
            return $this->serializeBookStatusChanged($event);
        }

        $eventClass = $event::class;
        throw new InvalidArgumentException("Unknown queueable event: $eventClass");
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBookStatusChanged(BookStatusChangedEvent $event): array
    {
        return [
            'bookId' => $event->bookId,
            'oldStatus' => $event->oldStatus->value,
            'newStatus' => $event->newStatus->value,
            'year' => $event->year,
        ];
    }
}
