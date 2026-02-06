<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Book;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Psr\Clock\ClockInterface;
use RuntimeException;

/**
 * @implements UseCaseInterface<CreateBookCommand, int>
 */
final readonly class CreateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookQueryServiceInterface $bookQueryService,
        private AuthorQueryServiceInterface $authorQueryService,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param CreateBookCommand $command
     */
    public function execute(object $command): int
    {
        $authorIds = $command->authorIds->toArray();

        if ($this->bookQueryService->existsByIsbn($command->isbn)) {
            throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
        }

        if ($authorIds !== []) {
            $missingIds = $this->authorQueryService->findMissingIds($authorIds);

            if ($missingIds !== []) {
                throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
            }
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

        $bookId = $this->bookRepository->save($book);

        if ($bookId === 0) {
            throw new RuntimeException('Failed to retrieve book ID after save');
        }

        return $bookId;
    }
}
