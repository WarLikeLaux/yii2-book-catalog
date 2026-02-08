<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\TranslatorInterface;
use app\infrastructure\queue\NotifySingleSubscriberJob;
use Psr\Log\LoggerInterface;
use yii\queue\Queue;

final readonly class NotifySubscribersHandler
{
    public function __construct(
        private SubscriptionQueryServiceInterface $queryService,
        private BookQueryServiceInterface $bookQueryService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(int $bookId, Queue $queue): void
    {
        $book = $this->bookQueryService->findById($bookId);

        if (!$book instanceof BookReadDto) {
            $this->logger->warning('Book not found for notification', ['book_id' => $bookId]);
            return;
        }

        $title = $book->title;
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
