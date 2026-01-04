<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\factories\BookYearFactory;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookUpdatedEvent;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Throwable;

final readonly class UpdateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private TransactionalEventPublisher $eventPublisher,
        private BookYearFactory $bookYearFactory,
    ) {
    }

    public function execute(UpdateBookCommand $command): void
    {
        $book = $this->bookRepository->getByIdAndVersion($command->id, $command->version);
        $oldYear = $book->year->value;
        $isPublished = $book->published;

        $this->transaction->begin();
        try {
            $book->rename($command->title);
            $book->changeYear($this->bookYearFactory->create($command->year));
            $book->correctIsbn(new Isbn($command->isbn));
            $book->updateDescription($command->description);

            if ($command->cover !== null) {
                $cover = $command->cover;
                if (is_string($cover)) {
                    $cover = new StoredFileReference($cover);
                }
                $book->updateCover($cover);
            }

            $book->replaceAuthors($command->authorIds);

            $this->bookRepository->save($book);

            $this->eventPublisher->publishAfterCommit(
                new BookUpdatedEvent($command->id, $oldYear, $command->year, $isPublished)
            );

            $this->transaction->commit();
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
