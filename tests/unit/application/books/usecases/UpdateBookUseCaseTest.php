<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\queries\BookReadDto;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionInterface&MockObject $transaction;
    private UpdateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->useCase = new UpdateBookUseCase($this->bookRepository, $this->transaction);
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

        $existingBook = new BookReadDto(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Old description',
            isbn: '9780132350884',
            authorIds: [1],
            authorNames: ['Author One'],
            coverUrl: '/uploads/old-cover.jpg'
        );

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($existingBook);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');
        $this->transaction->expects($this->never())->method('rollBack');

        $this->bookRepository->expects($this->once())->method('update');
        $this->bookRepository->expects($this->once())
            ->method('syncAuthors')
            ->with(42, [1, 2]);

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
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->transaction->expects($this->never())->method('begin');

        $this->expectException(DomainException::class);
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

        $existingBook = new BookReadDto(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: [],
            authorNames: [],
            coverUrl: null
        );

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->willReturn($existingBook);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('update')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);

        $this->useCase->execute($command);
    }
}
