<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\TranslatorInterface;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\YiiPsrLogger;
use yii\queue\Queue;

final readonly class NotifySubscribersHandler
{
    public function __construct(
        private SubscriptionQueryService $queryService,
        private TranslatorInterface $translator
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
                $bookId
            ));
            $totalDispatched++;
        }

        $logger = new YiiPsrLogger(LogCategory::SMS);
        $logger->info('SMS notification jobs dispatched', [
            'book_id' => $bookId,
            'book_title' => $title,
            'total_jobs' => $totalDispatched,
        ]);
    }
}
