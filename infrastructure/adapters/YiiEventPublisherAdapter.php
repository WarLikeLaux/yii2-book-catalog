<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\EventPublisherInterface;
use app\application\ports\QueueInterface;
use app\domain\events\BookCreatedEvent;
use app\domain\events\DomainEvent;
use app\infrastructure\queue\NotifySubscribersJob;

final readonly class YiiEventPublisherAdapter implements EventPublisherInterface
{
    public function __construct(
        private QueueInterface $queue
    ) {
    }

    /** @codeCoverageIgnore Работает с Yii-очередью, тестируется функционально */
    public function publishEvent(DomainEvent $event): void
    {
        if (!($event instanceof BookCreatedEvent)) {
            return;
        }

        /*
         * TODO: в проде лучше юзать Transactional Outbox. Иначе есть риск, что коммит отвалится,
         * а джоба уже улетит (фантомное уведомление).
         * Как вариант — хук 'afterCommit'.
         */
        $this->queue->push(new NotifySubscribersJob(
            $event->bookId,
            $event->title,
        ));
    }
}
