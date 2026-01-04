<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\factories\BookYearFactory;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\events\BookUpdatedEvent;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

final class UpdateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;

    private TransactionInterface&MockObject $transaction;

    private EventPublisherInterface&MockObject $eventPublisher;

    private BookYearFactory $bookYearFactory;

    private UpdateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));
        $this->bookYearFactory = new BookYearFactory($clock);

        $this->useCase = new UpdateBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->eventPublisher,
            $this->bookYearFactory
        );
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
            version: 1,
            cover: '/uploads/new-cover.jpg'
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Old Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Old description',
            coverImage: new StoredFileReference('/uploads/old-cover.jpg'),
            authorIds: [1],
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
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->title === 'Updated Title'
                    && $book->authorIds === [1, 2]));

        $this->eventPublisher->expects($this->once())
            ->method('publishEvent')
            ->with($this->callback(fn (BookUpdatedEvent $event) => $event->bookId === 42
                && $event->oldYear === 2020
                && $event->newYear === 2024
                && $event->isPublished === false));

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
            authorIds: [],
            version: 1
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('book.error.not_found'));

        $this->transaction->expects($this->never())->method('begin');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('book.error.not_found');

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
            authorIds: [],
            version: 1
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Old Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverImage: null,
            authorIds: [],
            published: false,
            version: 1
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

    public function testExecuteDoesNotUpdateCoverWhenCoverIsNull(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: 'New description',
            isbn: '9780132350884',
            authorIds: [1],
            version: 1,
            cover: null
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Old Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Old description',
            coverImage: new StoredFileReference('/uploads/old-cover.jpg'),
            authorIds: [1],
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

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->title === 'Updated Title'
                    && $book->coverImage?->getPath() === '/uploads/old-cover.jpg'));

        $this->useCase->execute($command);
    }

    public function testExecuteUpdatesIsbnCorrectly(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '979-10-90636-07-1',
            authorIds: [1],
            version: 1,
            cover: null
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverImage: null,
            authorIds: [1],
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

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->isbn->equals(new Isbn('979-10-90636-07-1'))));

        $this->useCase->execute($command);
    }

    public function testExecuteUpdatesCoverWithString(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: [1],
            version: 1,
            cover: '/uploads/new-cover.png'
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverImage: null,
            authorIds: [1],
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

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->coverImage?->getPath() === '/uploads/new-cover.png'));

        $this->useCase->execute($command);
    }

    public function testExecuteUpdatesDescriptionCorrectly(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2024,
            description: 'New description text',
            isbn: '9780132350884',
            authorIds: [1],
            version: 1,
            cover: null
        );

        $existingBook = Book::reconstitute(
            id: 42,
            title: 'Title',
            year: new BookYear(2020, new \DateTimeImmutable()),
            isbn: new Isbn('9780132350884'),
            description: 'Old description',
            coverImage: null,
            authorIds: [1],
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

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $book) => $book->description === 'New description text'));

        $this->useCase->execute($command);
    }
}
