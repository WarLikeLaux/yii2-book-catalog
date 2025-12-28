<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\events\BookCreatedEvent;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionInterface&MockObject $transaction;
    private EventPublisherInterface&MockObject $eventPublisher;
    private CacheInterface&MockObject $cache;
    private CreateBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->useCase = new CreateBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->eventPublisher,
            $this->cache
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
            cover: '/uploads/cover.jpg'
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');
        $this->transaction->expects($this->never())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Book $book) {
                return $book->getTitle() === 'Clean Code'
                    && $book->getAuthorIds() === [1, 2];
            }))
            ->willReturnCallback(function (Book $book) {
                $book->setId(42);
            });

        $this->eventPublisher->expects($this->once())
            ->method('publishEvent')
            ->with($this->callback(function (BookCreatedEvent $event): bool {
                return $event->bookId === 42 && $event->title === 'Clean Code';
            }));

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
            authorIds: [1]
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->eventPublisher->expects($this->never())->method('publishEvent');

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
            authorIds: [1]
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->eventPublisher->expects($this->never())->method('publishEvent');

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
            authorIds: []
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('commit');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $book) {
                $book->setId(1);
            });

        $this->eventPublisher->expects($this->once())->method('publishEvent');

        $result = $this->useCase->execute($command);

        $this->assertSame(1, $result);
    }
    public function testExecuteThrowsExceptionWhenIdNotReturned(): void
    {
        $command = new CreateBookCommand(
            title: 'Title',
            year: 2023,
            isbn: '978-3-16-148410-0',
            description: 'Desc',
            cover: 'http://cover.com',
            authorIds: [1, 2]
        );

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Book::class))
            ->willReturnCallback(function (Book $book) {
                // Do NOT set ID
            });
            
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to retrieve book ID after save');

        $this->useCase->execute($command);
    }
}
