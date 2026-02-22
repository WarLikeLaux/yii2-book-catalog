<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\values\AuthorIdCollection;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\BookIsbnCheckerInterface;
use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookStatus;
use app\domain\values\Isbn;
use BookTestHelper;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

final class UpdateBookUseCaseTest extends Unit
{
    private const DESCRIPTION_NEW = 'New description';
    private BookRepositoryInterface&MockObject $bookRepository;
    private BookIsbnCheckerInterface&MockObject $bookIsbnChecker;
    private AuthorExistenceCheckerInterface&MockObject $authorExistenceChecker;
    private ClockInterface&MockObject $clock;
    private UpdateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->bookIsbnChecker = $this->createMock(BookIsbnCheckerInterface::class);
        $this->bookIsbnChecker->method('existsByIsbn')->willReturn(false);
        $this->authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $this->authorExistenceChecker->method('existsAllByIds')->willReturn(true);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));

        $this->useCase = new UpdateBookUseCase(
            $this->bookRepository,
            $this->bookIsbnChecker,
            $this->authorExistenceChecker,
            $this->clock,
        );
    }

    public function testExecuteUpdatesBookSuccessfully(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: self::DESCRIPTION_NEW,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 2]),
            version: 1,
            storedCover: '/uploads/new-cover.jpg',
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Old description',
            coverImage: '/uploads/old-cover.jpg',
            authorIds: [1],
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === 'Updated Title'
                    && $book->authorIds === [1, 2]))
            ->willReturn(42);

        $this->useCase->execute($command);
    }

    public function testExecuteUpdatesBookWithDuplicateAuthorIds(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: self::DESCRIPTION_NEW,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 2, 1]),
            version: 1,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Old Title',
            year: 2020,
            description: 'Old description',
            coverImage: null,
            authorIds: [1],
            status: BookStatus::Draft,
            version: 1,
        );

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsAllByIds')
            ->with([1, 2])
            ->willReturn(true);

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->authorIds === [1, 2]))
            ->willReturn(42);

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
            ->willThrowException(new StaleDataException(DomainErrorCode::BookStaleData));

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
            status: BookStatus::Draft,
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
            description: self::DESCRIPTION_NEW,
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
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === 'Updated Title'
                    && $book->coverImage?->getPath() === '/uploads/old-cover.jpg'))
            ->willReturn(42);

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
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->isbn->equals(new Isbn('979-10-90636-07-1'))))
            ->willReturn(42);

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
            storedCover: '/uploads/new-cover.png',
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Description',
            authorIds: [1],
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->coverImage?->getPath() === '/uploads/new-cover.png'))
            ->willReturn(42);

        $this->useCase->execute($command);
    }

    public function testExecuteRemovesCoverWhenRemoveCoverIsTrue(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Title',
            year: 2024,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
            removeCover: true,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Description',
            coverImage: '/uploads/old-cover.jpg',
            authorIds: [1],
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->coverImage === null))
            ->willReturn(42);

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
            storedCover: null,
        );

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Title',
            year: 2020,
            description: 'Old description',
            authorIds: [1],
            status: BookStatus::Draft,
            version: 1,
        );

        $this->bookRepository->expects($this->once())
            ->method('getByIdAndVersion')
            ->with(42, 1)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->description === 'New description text'))
            ->willReturn(42);

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
            status: BookStatus::Draft,
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

    public function testExecuteThrowsIsbnAlreadyExistsException(): void
    {
        $bookIsbnChecker = $this->createMock(BookIsbnCheckerInterface::class);
        $bookIsbnChecker->method('existsByIsbn')
            ->willReturn(true);

        $useCase = new UpdateBookUseCase(
            $this->bookRepository,
            $bookIsbnChecker,
            $this->authorExistenceChecker,
            $this->clock,
        );

        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: self::DESCRIPTION_NEW,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 1,
        );

        $this->bookRepository->expects($this->never())->method('getByIdAndVersion');
        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(AlreadyExistsException::class);

        $useCase->execute($command);
    }

    public function testExecuteThrowsAuthorsNotFoundException(): void
    {
        $authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $authorExistenceChecker->method('existsAllByIds')
            ->willReturn(false);

        $useCase = new UpdateBookUseCase(
            $this->bookRepository,
            $this->bookIsbnChecker,
            $authorExistenceChecker,
            $this->clock,
        );

        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: self::DESCRIPTION_NEW,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 999]),
            version: 1,
        );

        $this->bookRepository->expects($this->never())->method('getByIdAndVersion');
        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute($command);
    }
}
