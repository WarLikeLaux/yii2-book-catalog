<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;

/**
 * @implements UseCaseInterface<DeleteBookCommand, bool>
 */
final readonly class DeleteBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
    ) {
    }

    /**
     * @param DeleteBookCommand $command
     */
    public function execute(object $command): bool
    {
        $book = $this->bookRepository->get($command->id);
        $book->markAsDeleted();

        $this->bookRepository->delete($book);

        return true;
    }
}
