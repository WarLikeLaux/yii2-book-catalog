<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\PublishBookCommand;
use app\application\books\usecases\PublishBookUseCase;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\events\BookPublishedEvent;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use BookTestHelper;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class PublishBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionInterface&MockObject $transaction;
    private TransactionalEventPublisher&MockObject $eventPublisher;
    private BookPublicationPolicy $publicationPolicy;
    private PublishBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->eventPublisher = $this->createMock(TransactionalEventPublisher::class);
        $this->publicationPolicy = new BookPublicationPolicy();
        $this->useCase = new PublishBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->eventPublisher,
            $this->publicationPolicy
        );
    }

    public function testPublishesBookSuccessfully(): void
    {
        $policy = $this->createMock(BookPublicationPolicy::class);
        $book = BookTestHelper::createBook(
            id: 42,
            title: 'Clean Code',
            year: 2008,
            description: 'A Handbook',
            authorIds: [1, 2],
            published: false
        );

        $policy->expects($this->once())
            ->method('ensureCanPublish')
            ->with($book);

        $command = new PublishBookCommand(bookId: 42);
        $useCase = new PublishBookUseCase(
            $this->bookRepository,
            $this->transaction,
            $this->eventPublisher,
            $policy
        );

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
            ->method('get')
            ->with(42)
            ->willReturn($book);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn (Book $b): bool => $b->published));

        $this->eventPublisher->expects($this->once())
            ->method('publishAfterCommit')
            ->with($this->callback(fn (BookPublishedEvent $e): bool => $e->bookId === 42
                    && $e->title === 'Clean Code'
                    && $e->year === 2008))
            ->willReturnCallback(function (): void {
                $this->transaction->afterCommit(fn(): null => null);
            });

        $useCase->execute($command);
    }

    public function testThrowsDomainExceptionWithoutAuthors(): void
    {
        $book = BookTestHelper::createBook(
            id: 42,
            title: 'Book Without Authors',
            year: 2024,
            description: 'Test',
            authorIds: [],
            published: false
        );

        $command = new PublishBookCommand(bookId: 42);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($book);

        $this->bookRepository->expects($this->never())->method('save');
        $this->eventPublisher->expects($this->never())->method('publishAfterCommit');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $this->useCase->execute($command);
    }

    public function testRollsBackOnRepositoryException(): void
    {
        $book = BookTestHelper::createBook(
            id: 42,
            title: 'Test Book',
            year: 2024,
            description: 'Test',
            authorIds: [1],
            published: false
        );

        $command = new PublishBookCommand(bookId: 42);

        $this->transaction->expects($this->once())->method('begin');
        $this->transaction->expects($this->never())->method('commit');
        $this->transaction->expects($this->once())->method('rollBack');

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->willReturn($book);

        $this->bookRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->eventPublisher->expects($this->never())->method('publishAfterCommit');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->useCase->execute($command);
    }
}
