<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookCreatedEvent;
use app\domain\events\DomainEvent;
use app\jobs\NotifySubscribersJob;

final class YiiEventPublisherAdapter implements EventPublisherInterface
{
    public function __construct(
        private readonly QueueInterface $queue
    ) {
    }

    public function publishEvent(DomainEvent $event): void
    {
        if (!($event instanceof BookCreatedEvent)) {
            return;
        }

        $this->queue->push(new NotifySubscribersJob([
            'bookId' => $event->bookId,
            'title' => $event->title,
        ]));
    }
}
