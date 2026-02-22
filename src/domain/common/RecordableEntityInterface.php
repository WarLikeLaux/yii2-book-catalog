<?php

declare(strict_types=1);

namespace app\domain\common;

use app\domain\events\DomainEvent;

interface RecordableEntityInterface extends IdentifiableEntityInterface
{
    /**
     * @return list<DomainEvent>
     */
    public function pullRecordedEvents(): array;
}
