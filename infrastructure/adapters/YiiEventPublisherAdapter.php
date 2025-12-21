<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\DomainEvent;
use app\jobs\NotifySubscribersJob;

final class YiiEventPublisherAdapter implements EventPublisherInterface
{
    public function __construct(
        private readonly QueueInterface $queue
    ) {
    }

    public function publish(string $eventType, array $payload): void
    {
        if ($eventType !== 'book.created') {
            return;
        }

        $this->queue->push(new NotifySubscribersJob([
            'bookId' => $payload['bookId'],
            'title' => $payload['title'],
        ]));
    }

    public function publishEvent(DomainEvent $event): void
    {
        $this->publish($event->getEventType(), $event->getPayload());
    }
}
