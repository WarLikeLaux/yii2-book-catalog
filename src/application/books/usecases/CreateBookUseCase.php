<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\BookIsbnCheckerInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Book;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Psr\Clock\ClockInterface;

/**
 * @implements UseCaseInterface<CreateBookCommand, int>
 */
final readonly class CreateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookIsbnCheckerInterface $bookIsbnChecker,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param CreateBookCommand $command
     */
    public function execute(object $command): int
    {
        $authorIds = $command->authorIds->toArray();

        if ($this->bookIsbnChecker->existsByIsbn($command->isbn)) {
            throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
        }

        if ($authorIds !== [] && !$this->authorExistenceChecker->existsAllByIds($authorIds)) {
            throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
        }

        $currentYear = (int) $this->clock->now()->format('Y');
        $coverImage = $command->storedCover !== null ? new StoredFileReference($command->storedCover) : null;

        $book = Book::create(
            title: $command->title,
            year: new BookYear($command->year, $currentYear),
            isbn: new Isbn($command->isbn),
            description: $command->description,
            coverImage: $coverImage,
        );
        $book->replaceAuthors($authorIds);

        return $this->bookRepository->save($book);
    }
}
