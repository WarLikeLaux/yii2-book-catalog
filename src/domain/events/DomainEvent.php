<?php

declare(strict_types=1);

namespace app\domain\events;

interface DomainEvent
{
    public function getEventType(): string;
}
