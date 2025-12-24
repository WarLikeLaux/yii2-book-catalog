<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\events\DomainEvent;

interface EventPublisherInterface
{
    public function publishEvent(DomainEvent $event): void;
}
