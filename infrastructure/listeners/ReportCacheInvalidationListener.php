<?php

declare(strict_types=1);

namespace app\infrastructure\listeners;

use app\application\ports\CacheInterface;
use app\application\ports\EventListenerInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookPublishedEvent;
use app\domain\events\BookUpdatedEvent;
use app\domain\events\DomainEvent;

final readonly class ReportCacheInvalidationListener implements EventListenerInterface
{
    private const string CACHE_KEY_FORMAT = 'report:top_authors:%d';

    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedEvents(): array
    {
        return [
            BookUpdatedEvent::class,
            BookDeletedEvent::class,
            BookPublishedEvent::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        match (true) {
            $event instanceof BookUpdatedEvent => $this->handleUpdate($event),
            $event instanceof BookDeletedEvent => $this->handleDelete($event),
            $event instanceof BookPublishedEvent => $this->handlePublish($event),
            default => null, // @codeCoverageIgnore
        };
    }

    private function handleUpdate(BookUpdatedEvent $event): void
    {
        if (!$event->isPublished) {
            return;
        }

        $this->invalidateYear($event->oldYear);

        if ($event->oldYear === $event->newYear) {
            return;
        }

        $this->invalidateYear($event->newYear);
    }

    private function handleDelete(BookDeletedEvent $event): void
    {
        if (!$event->wasPublished) {
            return;
        }

        $this->invalidateYear($event->year);
    }

    private function handlePublish(BookPublishedEvent $event): void
    {
        $this->invalidateYear($event->year);
    }

    private function invalidateYear(int $year): void
    {
        $this->cache->delete(sprintf(self::CACHE_KEY_FORMAT, $year));
    }
}
