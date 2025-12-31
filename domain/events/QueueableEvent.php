<?php

declare(strict_types=1);

namespace app\domain\events;

interface QueueableEvent extends DomainEvent
{
    /** @return class-string */
    public function getJobClass(): string;

    /** @return array<string, mixed> */
    public function getJobPayload(): array;
}
