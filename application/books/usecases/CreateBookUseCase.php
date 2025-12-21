<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookCreatedEvent;

final class CreateBookUseCase
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly TransactionInterface $transaction,
        private readonly EventPublisherInterface $eventPublisher,
    ) {
    }

    public function execute(CreateBookCommand $command): int
    {
        $this->transaction->begin();

        try {
            $bookId = $this->bookRepository->create(
                title: $command->title,
                year: $command->year,
                isbn: $command->isbn,
                description: $command->description,
                coverUrl: $command->cover
            );

            $this->bookRepository->syncAuthors($bookId, $command->authorIds);

            $this->transaction->commit();

            $event = new BookCreatedEvent(
                bookId: $bookId,
                title: $command->title
            );

            $this->eventPublisher->publish('book.created', [
                'bookId' => $event->bookId,
                'title' => $event->title,
            ]);

            return $bookId;
        } catch (\Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
