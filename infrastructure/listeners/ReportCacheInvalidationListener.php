<?php

declare(strict_types=1);

namespace app\infrastructure\listeners;

use app\application\ports\CacheInterface;
use app\application\ports\EventListenerInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\events\BookStatusChangedEvent;
use app\domain\events\BookUpdatedEvent;
use app\domain\events\DomainEvent;
use app\domain\values\BookStatus;

final readonly class ReportCacheInvalidationListener implements EventListenerInterface
{
    private const string CACHE_KEY_FORMAT = 'report:top_authors:%d';

    public function __construct(
        private CacheInterface $cache,
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
            BookStatusChangedEvent::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        match (true) {
            $event instanceof BookUpdatedEvent => $this->handleUpdate($event),
            $event instanceof BookDeletedEvent => $this->handleDelete($event),
            $event instanceof BookStatusChangedEvent => $this->handleStatusChanged($event),
            default => null, // @codeCoverageIgnore
        };
    }

    private function handleUpdate(BookUpdatedEvent $event): void
    {
        if ($event->status !== BookStatus::Published) {
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

    private function handleStatusChanged(BookStatusChangedEvent $event): void
    {
        if ($event->oldStatus !== BookStatus::Published && $event->newStatus !== BookStatus::Published) {
            return;
        }

        $this->cache->delete(sprintf(self::CACHE_KEY_FORMAT, (int)date('Y')));
    }

    private function invalidateYear(int $year): void
    {
        $this->cache->delete(sprintf(self::CACHE_KEY_FORMAT, $year));
    }
}
