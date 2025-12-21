<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\exceptions\DomainException;
use app\interfaces\FileStorageInterface;

final class UpdateBookUseCase
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly TransactionInterface $transaction,
        private readonly FileStorageInterface $fileStorage,
    ) {
    }

    public function execute(UpdateBookCommand $command): void
    {
        $book = $this->bookRepository->findById($command->id);
        if (!$book) {
            throw new DomainException('Book not found');
        }

        $this->transaction->begin();

        try {
            $coverUrl = $command->cover ? $this->fileStorage->save($command->cover) : null;
            $this->bookRepository->update(
                id: $command->id,
                title: $command->title,
                year: $command->year,
                isbn: $command->isbn,
                description: $command->description,
                coverUrl: $coverUrl
            );

            $this->bookRepository->syncAuthors($command->id, $command->authorIds);

            $this->transaction->commit();
        } catch (\Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
