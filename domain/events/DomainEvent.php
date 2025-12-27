<?php

declare(strict_types=1);

namespace app\domain\events;

interface DomainEvent
{
    public function getEventType(): string;

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
