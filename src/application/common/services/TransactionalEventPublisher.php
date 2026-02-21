<?php

declare(strict_types=1);

namespace app\application\common\services;

use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\DomainEvent;

final readonly class TransactionalEventPublisher
{
    public function __construct(
        private TransactionInterface $transaction,
        private EventPublisherInterface $publisher,
    ) {
    }

    /**
     * NOTE: Риск Dual Write. Событие может быть потеряно при сбое после коммита.
     * @see docs/DECISIONS.md (см. пункт "1. Отказ от Transactional Outbox")
     */
    public function publishAfterCommit(DomainEvent $event): void
    {
        $this->transaction->afterCommit(
            fn() => $this->publisher->publishEvent($event),
        );
    }
}
