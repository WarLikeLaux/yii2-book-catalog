<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookDeletedEvent;
use Throwable;

final readonly class DeleteBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private TransactionalEventPublisher $eventPublisher,
    ) {
    }

    public function execute(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->get($command->id);
        $year = $book->year->value;
        $wasPublished = $book->published;

        $this->transaction->begin();

        try {
            $this->bookRepository->delete($book);

            $this->eventPublisher->publishAfterCommit(
                new BookDeletedEvent($command->id, $year, $wasPublished),
            );

            $this->transaction->commit();
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
