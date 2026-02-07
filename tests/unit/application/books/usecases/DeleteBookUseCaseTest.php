<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\domain\events\BookDeletedEvent;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookStatus;
use BookTestHelper;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionalEventPublisher&MockObject $eventPublisher;
    private DeleteBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(TransactionalEventPublisher::class);
        $this->useCase = new DeleteBookUseCase(
            $this->bookRepository,
            $this->eventPublisher,
        );
    }

    public function testExecuteDeletesBookSuccessfully(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Book to Delete',
            year: 2020,
            authorIds: [],
            status: BookStatus::Draft,
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->with($existingBook);

        $this->eventPublisher->expects($this->once())
            ->method('publishAfterCommit')
            ->with($this->callback(static fn (BookDeletedEvent $event): bool => $event->bookId === 42
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
            ->willThrowException(new EntityNotFoundException(DomainErrorCode::BookNotFound));

        $this->bookRepository->expects($this->never())->method('delete');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookNotFound->value);

        $this->useCase->execute($command);
    }

    public function testExecuteRollsBackOnRepositoryException(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = BookTestHelper::createBook(
            id: 42,
            title: 'Book to Delete',
            year: 2020,
            authorIds: [],
            status: BookStatus::Published,
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);

        $this->useCase->execute($command);
    }
}
