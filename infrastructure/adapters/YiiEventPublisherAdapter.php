<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\DomainEvent;
use app\domain\events\QueueableEvent;

final readonly class YiiEventPublisherAdapter implements EventPublisherInterface
{
    /** @var EventListenerInterface[] */
    private array $listeners;

    public function __construct(
        private QueueInterface $queue,
        EventListenerInterface ...$listeners
    ) {
        $this->listeners = $listeners;
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
        if (!($event instanceof QueueableEvent)) {
            return;
        }

        $jobClass = $event->getJobClass();
        $payload = $event->getJobPayload();

        /** @var \yii\queue\JobInterface $job */
        $job = new $jobClass(...$payload);
        $this->queue->push($job);
    }
}
