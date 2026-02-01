<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\common\exceptions\ApplicationException;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\exceptions\DomainException;

/**
 * @implements UseCaseInterface<DeleteBookCommand, bool>
 */
final readonly class DeleteBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionalEventPublisher $eventPublisher,
    ) {
    }

    /**
     * @param DeleteBookCommand $command
     */
    public function execute(object $command): bool
    {
        try {
            $book = $this->bookRepository->get($command->id);
            $year = $book->year->value;
            $wasPublished = $book->published;

            $this->bookRepository->delete($book);

            $this->eventPublisher->publishAfterCommit(
                new BookDeletedEvent($command->id, $year, $wasPublished),
            );

            return true;
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
