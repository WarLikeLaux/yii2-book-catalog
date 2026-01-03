<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\PublishBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookPublishedEvent;
use app\domain\services\BookPublicationPolicy;
use Throwable;

final readonly class PublishBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private EventPublisherInterface $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    public function execute(PublishBookCommand $command): void
    {
        $this->transaction->begin();

        try {
            $book = $this->bookRepository->get($command->bookId);

            $this->publicationPolicy->ensureCanPublish($book);
            $book->publish();

            $this->bookRepository->save($book);

            $title = $book->title;
            $year = $book->year->value;

            $this->transaction->afterCommit(function () use ($command, $title, $year): void {
                $this->eventPublisher->publishEvent(
                    new BookPublishedEvent($command->bookId, $title, $year)
                );
            });

            $this->transaction->commit();
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
