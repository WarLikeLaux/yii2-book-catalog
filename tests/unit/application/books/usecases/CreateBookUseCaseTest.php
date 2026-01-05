<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\exceptions\DomainException;
use BookTestHelper;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

final class CreateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionInterface&MockObject $transaction;
    private ClockInterface&MockObject $clock;
    private CreateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));

        $this->useCase = new CreateBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->clock,
        );
    }

    public function testExecuteCreatesBookSuccessfully(): void
    {
        $command = new CreateBookCommand(
            title: 'Clean Code',
            year: 2008,
            description: 'A Handbook of Agile Software Craftsmanship',
            isbn: '9780132350884',
            authorIds: [1, 2],
            cover: '/uploads/cover.jpg',
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');
        $this->transaction->expects($this->never())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === 'Clean Code'
                    && $book->authorIds === [1, 2]))
            ->willReturnCallback(static function (Book $book): void {
                BookTestHelper::assignBookId($book, 42);
            });

        $result = $this->useCase->execute($command);

        $this->assertSame(42, $result);
    }

    public function testExecuteRollsBackOnRepositoryException(): void
    {
        $command = new CreateBookCommand(
            title: 'Test Book',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: [1],
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->useCase->execute($command);
    }

    public function testExecuteRollsBackOnInvalidIsbn(): void
    {
        $command = new CreateBookCommand(
            title: 'Test Book',
            year: 2024,
            description: 'Description',
            isbn: 'invalid-isbn',
            authorIds: [1],
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->expectException(DomainException::class);

        $this->useCase->execute($command);
    }

    public function testExecuteCreatesBookWithoutCover(): void
    {
        $command = new CreateBookCommand(
            title: 'No Cover Book',
            year: 2024,
            description: 'A book without cover',
            isbn: '9780132350884',
            authorIds: [],
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Book $book): void {
                BookTestHelper::assignBookId($book, 1);
            });

        $result = $this->useCase->execute($command);

        $this->assertSame(1, $result);
    }

    public function testExecuteThrowsExceptionWhenIdNotReturned(): void
    {
        $command = new CreateBookCommand(
            title: 'Title',
            year: 2023,
            description: 'Desc',
            isbn: '978-3-16-148410-0',
            authorIds: [1, 2],
            cover: 'http://cover.com',
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Book::class))
            ->willReturnCallback(static function (Book $_book): void {
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to retrieve book ID after save');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionOnFutureYear(): void
    {
        $command = new CreateBookCommand(
            title: 'Future Book',
            year: 2026,
            description: 'A book from the future',
            isbn: '9780132350884',
            authorIds: [1],
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');
        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');

        $this->useCase->execute($command);
    }
}
