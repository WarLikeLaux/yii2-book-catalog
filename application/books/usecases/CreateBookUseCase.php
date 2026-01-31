<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Book;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Psr\Clock\ClockInterface;
use RuntimeException;

/**
 * @implements UseCaseInterface<CreateBookCommand, int>
 */
final readonly class CreateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param CreateBookCommand $command
     */
    public function execute(object $command): int
    {
        $currentYear = (int) $this->clock->now()->format('Y');

        $book = Book::create(
            title: $command->title,
            year: new BookYear($command->year, $currentYear),
            isbn: new Isbn($command->isbn),
            description: $command->description,
            coverImage: $command->storedCover,
        );
        $book->replaceAuthors($command->authorIds->toArray());

        $this->bookRepository->save($book);
        $bookId = $book->id;

        if ($bookId === null) {
            throw new RuntimeException('Failed to retrieve book ID after save');
        }

        return $bookId;
    }
}
