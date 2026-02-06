<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\events\BookUpdatedEvent;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Psr\Clock\ClockInterface;

/**
 * @implements UseCaseInterface<UpdateBookCommand, bool>
 */
final readonly class UpdateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookQueryServiceInterface $bookQueryService,
        private AuthorQueryServiceInterface $authorQueryService,
        private TransactionalEventPublisher $eventPublisher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param UpdateBookCommand $command
     */
    public function execute(object $command): bool
    {
        $authorIds = $command->authorIds->toArray();

        if ($this->bookQueryService->existsByIsbn($command->isbn, $command->id)) {
            throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
        }

        if ($authorIds !== []) {
            $missingIds = $this->authorQueryService->findMissingIds($authorIds);

            if ($missingIds !== []) {
                throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
            }
        }

        $currentYear = (int) $this->clock->now()->format('Y');

        $book = $this->bookRepository->getByIdAndVersion($command->id, $command->version);
        $oldYear = $book->year->value;
        $isPublished = $book->published;

        $book->rename($command->title);
        $book->changeYear(new BookYear($command->year, $currentYear));
        $book->correctIsbn(new Isbn($command->isbn));
        $book->updateDescription($command->description);

        if ($command->storedCover !== null) {
            $book->updateCover(new StoredFileReference($command->storedCover));
        }

        $book->replaceAuthors($authorIds);

        $this->bookRepository->save($book);

        $this->eventPublisher->publishAfterCommit(
            new BookUpdatedEvent($command->id, $oldYear, $command->year, $isPublished),
        );

        return true;
    }
}
