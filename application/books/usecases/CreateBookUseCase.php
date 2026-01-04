<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Throwable;

final readonly class CreateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
        private ClockInterface $clock,
    ) {
    }

    public function execute(CreateBookCommand $command): int
    {
        $this->transaction->begin();

        try {
            $cover = $command->cover;

            if (is_string($cover)) {
                $cover = new StoredFileReference($cover);
            }

            $book = Book::create(
                title: $command->title,
                year: new BookYear($command->year, $this->clock->now()),
                isbn: new Isbn($command->isbn),
                description: $command->description,
                coverImage: $cover
            );
            $book->replaceAuthors($command->authorIds);

            $this->bookRepository->save($book);
            $bookId = $book->id;

            if ($bookId === null) {
                throw new RuntimeException('Failed to retrieve book ID after save');
            }

            $this->transaction->commit();

            return $bookId;
        } catch (Throwable $e) {
            $this->transaction->rollBack();
            throw $e;
        }
    }
}
