<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\application\ports\TransactionInterface;
use app\domain\values\BookYear;
use app\domain\values\Isbn;

final readonly class UpdateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private CacheInterface $cache,
    ) {
    }

    public function execute(UpdateBookCommand $command): void
    {
        $book = $this->bookRepository->get($command->id);

        $oldYear = $book->getYear()->value;

        $this->transaction->begin();

        try {
            $book->update(
                title: $command->title,
                year: new BookYear($command->year),
                isbn: new Isbn($command->isbn),
                description: $command->description,
                coverUrl: $command->cover
            );
            $book->syncAuthors($command->authorIds);

            $this->bookRepository->save($book);

            $this->transaction->commit();

            $this->cache->delete(sprintf('report:top_authors:%d', $oldYear));
            if ($command->year !== $oldYear) {
                $this->cache->delete(sprintf('report:top_authors:%d', $command->year));
            }
        } catch (\Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
