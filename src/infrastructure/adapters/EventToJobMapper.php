<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\QueueableEvent;
use yii\queue\JobInterface;

final readonly class EventToJobMapper implements EventToJobMapperInterface
{
    public function __construct(
        private EventJobMappingRegistry $registry,
    ) {
    }

    public function map(QueueableEvent $event): ?JobInterface
    {
        if (!$this->registry->has($event::class)) {
            return null;
        }

        return $this->registry->resolve($event);
    }
}
