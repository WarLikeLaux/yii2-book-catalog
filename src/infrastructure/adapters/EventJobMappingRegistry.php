<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\domain\events\QueueableEvent;
use InvalidArgumentException;
use ReflectionClass;
use yii\queue\JobInterface;

/**
 * @phpstan-type JobFactory callable(QueueableEvent): JobInterface
 * @phpstan-type JobMapping JobFactory|class-string<JobInterface>
 */
final readonly class EventJobMappingRegistry
{
    /**
     * @param array<class-string<QueueableEvent>, JobMapping> $mappings
     */
    public function __construct(
        private array $mappings,
        private EventSerializer $serializer,
    ) {
    }

    public function resolve(QueueableEvent $event): ?JobInterface
    {
        $eventClass = $event::class;

        if (!isset($this->mappings[$eventClass])) {
            throw new InvalidArgumentException("No job mapping for event: $eventClass");
        }

        $mapping = $this->mappings[$eventClass];

        if (is_callable($mapping)) {
            return $mapping($event);
        }

        return $this->instantiateJob($mapping, $event);
    }

    public function has(string $eventClass): bool
    {
        return isset($this->mappings[$eventClass]);
    }

    /**
     * @param class-string<JobInterface> $jobClass
     */
    private function instantiateJob(string $jobClass, QueueableEvent $event): JobInterface
    {
        $reflection = new ReflectionClass($jobClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $payload = $this->serializer->serialize($event);
        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (!array_key_exists($name, $payload)) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                throw new InvalidArgumentException(
                    "Missing required parameter '$name' for job $jobClass",
                );
            }

            $args[] = $payload[$name];
        }

        return $reflection->newInstanceArgs($args);
    }
}
