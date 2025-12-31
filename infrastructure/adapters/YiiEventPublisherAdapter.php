<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use app\application\ports\EventListenerInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookPublishedEvent;
use app\domain\events\DomainEvent;
use app\infrastructure\queue\NotifySubscribersJob;

final readonly class YiiEventPublisherAdapter implements EventPublisherInterface
{
    /**
     * @param array<EventListenerInterface> $listeners
     */
    public function __construct(
        private QueueInterface $queue,
        private array $listeners = []
    ) {
    }

    public function publishEvent(DomainEvent $event): void
    {
        $this->dispatchToListeners($event);
        $this->dispatchToQueue($event);
    }

    private function dispatchToListeners(DomainEvent $event): void
    {
        foreach ($this->listeners as $listener) {
            if (!in_array($event::class, $listener->subscribedEvents(), true)) {
                continue;
            }

            $listener->handle($event);
        }
    }

    private function dispatchToQueue(DomainEvent $event): void
    {
        if (!($event instanceof BookPublishedEvent)) {
            return;
        }

        $this->queue->push(new NotifySubscribersJob(
            $event->bookId,
            $event->title,
        ));
    }
}
