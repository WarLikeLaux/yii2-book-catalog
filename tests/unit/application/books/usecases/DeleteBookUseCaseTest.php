<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\events\BookDeletedEvent;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;

    private TransactionInterface&MockObject $transaction;

    private EventPublisherInterface&MockObject $eventPublisher;

    private DeleteBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);
        $this->useCase = new DeleteBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->eventPublisher
        );
    }

    public function testExecuteDeletesBookSuccessfully(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Book to Delete',
            year: new BookYear(2020),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverUrl: null,
            authorIds: [],
            published: false,
            version: 1
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingBook);

        $afterCommitCallback = null;
        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())
            ->method('afterCommit')
            ->willReturnCallback(function (callable $callback) use (&$afterCommitCallback): void {
                $afterCommitCallback = $callback;
            });
        $this->transaction->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$afterCommitCallback): void {
                if ($afterCommitCallback === null) {
                    return;
                }

                $afterCommitCallback();
            });
        $this->transaction->expects($this->never())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->with($existingBook);

        $this->eventPublisher->expects($this->once())
            ->method('publishEvent')
            ->with($this->callback(fn (BookDeletedEvent $event) => $event->bookId === 42
                && $event->year === 2020
                && $event->wasPublished === false));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenBookNotFound(): void
    {
        $command = new DeleteBookCommand(id: 999);

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('Book not found'));

        $this->transaction->expects($this->never())->method('begin');
        $this->bookRepository->expects($this->never())->method('delete');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Book not found');

        $this->useCase->execute($command);
    }

    public function testExecuteRollsBackOnRepositoryException(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Book to Delete',
            year: new BookYear(2020),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverUrl: null,
            authorIds: [],
            published: true,
            version: 1
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->willReturn($existingBook);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);

        $this->useCase->execute($command);
    }
}
