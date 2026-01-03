<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\factories\BookYearFactory;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookUpdatedEvent;
use app\domain\values\Isbn;
use Throwable;

final readonly class UpdateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private EventPublisherInterface $eventPublisher,
        private BookYearFactory $bookYearFactory,
    ) {
    }

    public function execute(UpdateBookCommand $command): void
    {
        $book = $this->bookRepository->get($command->id);
        $oldYear = $book->year->value;
        $isPublished = $book->published;

        $this->transaction->begin();
        try {
            $book->rename($command->title);
            $book->changeYear($this->bookYearFactory->create($command->year));
            $book->correctIsbn(new Isbn($command->isbn));
            $book->updateDescription($command->description);

            $book->replaceAuthors($command->authorIds);

            $this->bookRepository->save($book);

            $this->transaction->afterCommit(function () use ($command, $oldYear, $isPublished): void {
                $this->eventPublisher->publishEvent(
                    new BookUpdatedEvent($command->id, $oldYear, $command->year, $isPublished)
                );
            });

            $this->transaction->commit();
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
