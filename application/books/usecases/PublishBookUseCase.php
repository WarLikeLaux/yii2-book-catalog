<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\PublishBookCommand;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookPublishedEvent;
use app\domain\services\BookPublicationPolicy;
use Throwable;

final readonly class PublishBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private TransactionalEventPublisher $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    public function execute(PublishBookCommand $command): void
    {
        $this->transaction->begin();

        try {
            $book = $this->bookRepository->get($command->bookId);

            $book->publish($this->publicationPolicy);

            $this->bookRepository->save($book);

            $this->eventPublisher->publishAfterCommit(
                new BookPublishedEvent($command->bookId, $book->title, $book->year->value)
            );

            $this->transaction->commit();
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
