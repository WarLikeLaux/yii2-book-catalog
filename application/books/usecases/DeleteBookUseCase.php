<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\domain\exceptions\DomainException;

final class DeleteBookUseCase
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository
    ) {
    }

    public function execute(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->findById($command->id);
        if (!$book) {
            throw new DomainException('Book not found');
        }

        $this->bookRepository->delete($command->id);
    }
}
