<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\BookPublishedEvent;
use app\domain\events\QueueableEvent;
use app\infrastructure\queue\NotifySubscribersJob;
use InvalidArgumentException;
use yii\queue\JobInterface;

final readonly class EventToJobMapper implements EventToJobMapperInterface
{
    public function map(QueueableEvent $event): JobInterface
    {
        return match ($event::class) {
            BookPublishedEvent::class => $this->mapBookPublished($event),
            default => throw new InvalidArgumentException(
                'No job mapping for event: ' . $event::class
            ),
        };
    }

    private function mapBookPublished(BookPublishedEvent $event): NotifySubscribersJob
    {
        return new NotifySubscribersJob(
            bookId: $event->bookId,
            title: $event->title,
        );
    }
}
