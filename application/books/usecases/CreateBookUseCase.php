<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final readonly class CreateBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionInterface $transaction,
    ) {
    }

    public function execute(CreateBookCommand $command): int
    {
        $this->transaction->begin();
        try {
            $book = Book::create(
                title: $command->title,
                year: new BookYear($command->year, new DateTimeImmutable()),
                isbn: new Isbn($command->isbn),
                description: $command->description,
                coverUrl: null
            );
            $book->replaceAuthors($command->authorIds);

            $this->bookRepository->save($book);
            $bookId = $book->getId();

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
