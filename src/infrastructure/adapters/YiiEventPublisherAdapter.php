<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\EventListenerInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\DomainEvent;
use app\domain\events\QueueableEvent;
use Psr\Log\LoggerInterface;
use Throwable;
use yii\queue\JobInterface;

final readonly class YiiEventPublisherAdapter implements EventPublisherInterface
{
    /** @var EventListenerInterface[] */
    private array $listeners;

    public function __construct(
        private QueueInterface $queue,
        private EventToJobMapperInterface $jobMapper,
        private LoggerInterface $logger,
        EventListenerInterface ...$listeners,
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

            try {
                $listener->handle($event);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'listener' => $listener::class,
                    'event' => $event::class,
                    'exception' => $e,
                ]);
            }
        }
    }

    private function dispatchToQueue(DomainEvent $event): void
    {
        if (!($event instanceof QueueableEvent)) {
            return;
        }

        $job = $this->jobMapper->map($event);

        if (!$job instanceof JobInterface) {
            return;
        }

        try {
            $this->queue->push($job);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'job' => $job::class,
                'event' => $event::class,
                'exception' => $e,
            ]);
        }
    }
}
