<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\events\BookCreatedEvent;
use app\domain\values\BookYear;
use app\domain\values\Isbn;

final readonly class CreateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private EventPublisherInterface $eventPublisher,
        private CacheInterface $cache,
    ) {
    }

    public function execute(CreateBookCommand $command): int
    {
        $this->transaction->begin();

        try {
            $bookId = $this->bookRepository->create(
                title: $command->title,
                year: new BookYear($command->year),
                isbn: new Isbn($command->isbn),
                description: $command->description,
                coverUrl: $command->cover
            );

            $this->bookRepository->syncAuthors($bookId, $command->authorIds);

            $this->transaction->commit();

            $this->cache->delete(sprintf('report:top_authors:%d', $command->year));

            $event = new BookCreatedEvent(
                bookId: $bookId,
                title: $command->title
            );

            $this->eventPublisher->publishEvent($event);

            return $bookId;
        } catch (\Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
