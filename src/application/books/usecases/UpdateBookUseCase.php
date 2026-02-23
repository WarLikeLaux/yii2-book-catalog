<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\BookIsbnCheckerInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\repositories\BookRepositoryInterface;
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
        private BookIsbnCheckerInterface $bookIsbnChecker,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param UpdateBookCommand $command
     */
    public function execute(object $command): bool
    {
        $authorIds = $command->authorIds->toArray();
        $isbn = new Isbn($command->isbn);

        if ($this->bookIsbnChecker->existsByIsbn($command->isbn, $command->id)) {
            throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
        }

        if ($authorIds !== [] && !$this->authorExistenceChecker->existsAllByIds($authorIds)) {
            throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
        }

        $currentYear = (int) $this->clock->now()->format('Y');

        $book = $this->bookRepository->getByIdAndVersion($command->id, $command->version);

        $book->rename($command->title);
        $book->changeYear(new BookYear($command->year, $currentYear));
        $book->correctIsbn($isbn);
        $book->updateDescription($command->description);

        if ($command->removeCover) {
            $book->updateCover(null);
        } elseif ($command->storedCover !== null) {
            $book->updateCover(new StoredFileReference($command->storedCover));
        }

        $book->replaceAuthors($authorIds);

        $this->bookRepository->save($book);

        return true;
    }
}
