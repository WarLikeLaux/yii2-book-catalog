<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\values\AuthorIdCollection;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\BookIsbnCheckerInterface;
use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\EntityNotFoundException;
use BookTestHelper;
use Codeception\Test\Unit;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

final class CreateBookUseCaseTest extends Unit
{
    private const TITLE_CLEAN_CODE = 'Clean Code';
    private const SUBTITLE_CLEAN_CODE = 'A Handbook of Agile Software Craftsmanship';
    private BookRepositoryInterface&MockObject $bookRepository;
    private BookIsbnCheckerInterface&MockObject $bookIsbnChecker;
    private AuthorExistenceCheckerInterface&MockObject $authorExistenceChecker;
    private ClockInterface&MockObject $clock;
    private CreateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->bookIsbnChecker = $this->createMock(BookIsbnCheckerInterface::class);
        $this->bookIsbnChecker->method('existsByIsbn')->willReturn(false);
        $this->authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $this->authorExistenceChecker->method('existsAllByIds')->willReturn(true);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-06-15'));

        $this->useCase = new CreateBookUseCase(
            $this->bookRepository,
            $this->bookIsbnChecker,
            $this->authorExistenceChecker,
            $this->clock,
        );
    }

    public function testExecuteCreatesBookSuccessfully(): void
    {
        $command = new CreateBookCommand(
            title: self::TITLE_CLEAN_CODE,
            year: 2008,
            description: self::SUBTITLE_CLEAN_CODE,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 2]),
            storedCover: '/uploads/cover.jpg',
        );

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->title === self::TITLE_CLEAN_CODE
                    && $book->authorIds === [1, 2]))
            ->willReturnCallback(static function (Book $book): int {
                BookTestHelper::assignBookId($book, 42);
                return 42;
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
            authorIds: AuthorIdCollection::fromArray([1]),
        );

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
            authorIds: AuthorIdCollection::fromArray([1]),
        );

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
            authorIds: AuthorIdCollection::fromArray([]),
        );

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Book $book): int {
                BookTestHelper::assignBookId($book, 1);
                return 1;
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
            authorIds: AuthorIdCollection::fromArray([1, 2]),
            storedCover: 'https://cover.com',
        );

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Book::class))
            ->willReturnCallback(static fn (Book $_book): int => 0);

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage(DomainErrorCode::EntityIdMissing->value);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionOnFutureYear(): void
    {
        $command = new CreateBookCommand(
            title: 'Future Book',
            year: 2026,
            description: 'A book from the future',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
        );

        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('year.error.future');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsIsbnAlreadyExistsException(): void
    {
        $bookIsbnChecker = $this->createMock(BookIsbnCheckerInterface::class);
        $bookIsbnChecker->method('existsByIsbn')
            ->with('9780132350884')
            ->willReturn(true);

        $useCase = new CreateBookUseCase(
            $this->bookRepository,
            $bookIsbnChecker,
            $this->authorExistenceChecker,
            $this->clock,
        );

        $command = new CreateBookCommand(
            title: self::TITLE_CLEAN_CODE,
            year: 2008,
            description: self::SUBTITLE_CLEAN_CODE,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
        );

        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(AlreadyExistsException::class);

        $useCase->execute($command);
    }

    public function testExecuteThrowsAuthorsNotFoundException(): void
    {
        $authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $authorExistenceChecker->method('existsAllByIds')
            ->with([1, 999])
            ->willReturn(false);

        $useCase = new CreateBookUseCase(
            $this->bookRepository,
            $this->bookIsbnChecker,
            $authorExistenceChecker,
            $this->clock,
        );

        $command = new CreateBookCommand(
            title: self::TITLE_CLEAN_CODE,
            year: 2008,
            description: self::SUBTITLE_CLEAN_CODE,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 999]),
        );

        $this->bookRepository->expects($this->never())->method('save');

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute($command);
    }

    public function testExecuteCreatesBookWithDuplicateAuthorIds(): void
    {
        $command = new CreateBookCommand(
            title: self::TITLE_CLEAN_CODE,
            year: 2008,
            description: self::SUBTITLE_CLEAN_CODE,
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 2, 1]),
        );

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsAllByIds')
            ->with([1, 2, 1])
            ->willReturn(true);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Book $book): bool => $book->authorIds === [1, 2]))
            ->willReturnCallback(static function (Book $book): int {
                BookTestHelper::assignBookId($book, 42);
                return 42;
            });

        $result = $this->useCase->execute($command);

        $this->assertSame(42, $result);
    }
}
