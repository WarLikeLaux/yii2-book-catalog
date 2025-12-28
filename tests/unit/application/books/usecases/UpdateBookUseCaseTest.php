<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;

    private TransactionInterface&MockObject $transaction;

    private CacheInterface&MockObject $cache;

    private UpdateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->useCase = new UpdateBookUseCase($this->bookRepository, $this->transaction, $this->cache);
    }

    public function testExecuteUpdatesBookSuccessfully(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: 'New description',
            isbn: '9780132350884',
            authorIds: [1, 2],
            cover: '/uploads/new-cover.jpg'
        );

        $existingBook = new Book(
            id: 42,
            title: 'Old Title',
            year: new BookYear(2020),
            isbn: new Isbn('9780132350884'),
            description: 'Old description',
            coverUrl: '/uploads/old-cover.jpg',
            authorIds: [1]
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingBook);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');
        $this->transaction->expects($this->never())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->getTitle() === 'Updated Title'
                    && $book->getAuthorIds() === [1, 2]));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenBookNotFound(): void
    {
        $command = new UpdateBookCommand(
            id: 999,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: []
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('Book not found'));

        $this->transaction->expects($this->never())->method('begin');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Book not found');

        $this->useCase->execute($command);
    }

    public function testExecuteRollsBackOnRepositoryException(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: []
        );

        $existingBook = new Book(
            id: 42,
            title: 'Old Title',
            year: new BookYear(2020),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverUrl: null,
            authorIds: [],
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->willReturn($existingBook);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);

        $this->useCase->execute($command);
    }
}
