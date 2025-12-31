<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\events\DomainEvent;

interface EventListenerInterface
{
    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedEvents(): array;

    public function handle(DomainEvent $event): void;
}
