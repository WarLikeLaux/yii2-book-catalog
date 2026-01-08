<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\TranslatorInterface;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use Psr\Log\LoggerInterface;
use yii\queue\Queue;

final readonly class NotifySubscribersHandler
{
    /**
     * Create a NotifySubscribersHandler with its required services.
     *
     * @param SubscriptionQueryServiceInterface $queryService Provides subscriber phone numbers for a given book.
     * @param TranslatorInterface $translator Translates notification messages (domain 'app').
     * @param LoggerInterface $logger Logs dispatch activity and results.
     */
    public function __construct(
        private SubscriptionQueryServiceInterface $queryService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(int $bookId, string $title, Queue $queue): void
    {
        $message = $this->translator->translate('app', 'notification.book.released', ['title' => $title]);
        $totalDispatched = 0;

        foreach ($this->queryService->getSubscriberPhonesForBook($bookId) as $phone) {
            $queue->push(new NotifySingleSubscriberJob(
                $phone,
                $message,
                $bookId,
            ));
            $totalDispatched++;
        }

        $this->logger->info('SMS notification jobs dispatched', [
            'book_id' => $bookId,
            'book_title' => $title,
            'total_jobs' => $totalDispatched,
        ]);
    }
}