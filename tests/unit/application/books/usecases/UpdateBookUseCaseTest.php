<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book;
use app\domain\events\BookUpdatedEvent;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\AuthorIdCollection;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use BookTestHelper;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

final class UpdateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionalEventPublisher&MockObject $eventPublisher;
    private ClockInterface&MockObject $clock;
    private UpdateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(TransactionalEventPublisher::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));

        $this->useCase = new UpdateBookUseCase(
            $this->bookRepository,
            $this->eventPublisher,
            $this->clock,
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
            authorIds: AuthorIdCollection::fromArray([1, 2]),
            version: 1,
            storedCover: new StoredFileReference('/uploads/new-cover.jpg'),
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Old description',
            coverImage: '/uploads/old-cover.jpg',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === 'Updated Title'
                    && $book->authorIds === [1, 2]));

        $this->eventPublisher->expects($this->once())
            ->method('publishAfterCommit')
            ->with($this->callback(static fn (BookUpdatedEvent $event): bool => $event->bookId === 42
                && $event->oldYear === 2020
                && $event->newYear === 2024
                && $event->isPublished === false));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsStaleDataExceptionWhenVersionMismatchOrNotFound(): void
    {
        $command = new UpdateBookCommand(
            id: 999,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(999, 1)
            ->willThrowException(new StaleDataException());

        $this->expectException(StaleDataException::class);

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
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Description',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

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
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Old description',
            coverImage: '/uploads/old-cover.jpg',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === 'Updated Title'
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
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Description',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->isbn->equals(new Isbn('979-10-90636-07-1'))));

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
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
            storedCover: new StoredFileReference('/uploads/new-cover.png'),
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Description',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->coverImage?->getPath() === '/uploads/new-cover.png'));

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
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
            storedCover: new StoredFileReference(':284'),
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Old description',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->description === 'New description text'));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionOnFutureYear(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2026,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Description',
            authorIds: [1],
            published: false,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');

        $this->useCase->execute($command);
    }
}
