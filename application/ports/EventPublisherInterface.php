<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\events\DomainEvent;

interface EventPublisherInterface
{
    public function publish(string $eventType, array $payload): void;

    public function publishEvent(DomainEvent $event): void;
}
