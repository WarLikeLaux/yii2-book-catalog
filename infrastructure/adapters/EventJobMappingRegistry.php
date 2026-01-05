<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\QueueableEvent;
use InvalidArgumentException;
use yii\queue\JobInterface;

/**
 * @phpstan-type JobFactory callable(QueueableEvent): JobInterface
 */
final readonly class EventJobMappingRegistry
{
    /**
     * @param array<class-string<QueueableEvent>, JobFactory> $mappings
     */
    public function __construct(private array $mappings)
    {
    }

    public function resolve(QueueableEvent $event): JobInterface
    {
        $eventClass = $event::class;

        if (!isset($this->mappings[$eventClass])) {
            throw new InvalidArgumentException("No job mapping for event: $eventClass");
        }

        return ($this->mappings[$eventClass])($event);
    }

    public function has(string $eventClass): bool
    {
        return isset($this->mappings[$eventClass]);
    }
}
