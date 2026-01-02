<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\PublishBookCommand;
use app\application\books\usecases\PublishBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\EventPublisherInterface;
use app\application\ports\TransactionInterface;
use app\domain\entities\Book;
use app\domain\events\BookPublishedEvent;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class PublishBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;

    private TransactionInterface&MockObject $transaction;

    private EventPublisherInterface&MockObject $eventPublisher;

    private BookPublicationPolicy $publicationPolicy;

    private PublishBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);
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
        $book = Book::reconstitute(
            id: 42,
            title: 'Clean Code',
            year: new BookYear(2008),
            isbn: new Isbn('9780132350884'),
            description: 'A Handbook',
            coverUrl: null,
            authorIds: [1, 2],
            published: false,
            version: 1
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
            ->with($this->callback(fn (Book $b) => $b->isPublished() === true));

        $this->eventPublisher->expects($this->once())
            ->method('publishEvent')
            ->with($this->callback(fn (BookPublishedEvent $e) => $e->bookId === 42
                    && $e->title === 'Clean Code'
                    && $e->year === 2008));

        $useCase->execute($command);
    }

    public function testThrowsDomainExceptionWithoutAuthors(): void
    {
        $book = Book::reconstitute(
            id: 42,
            title: 'Book Without Authors',
            year: new BookYear(2024),
            isbn: new Isbn('9780132350884'),
            description: 'Test',
            coverUrl: null,
            authorIds: [],
            published: false,
            version: 1
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
        $this->eventPublisher->expects($this->never())->method('publishEvent');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.publish_without_authors');

        $this->useCase->execute($command);
    }

    public function testRollsBackOnRepositoryException(): void
    {
        $book = Book::reconstitute(
            id: 42,
            title: 'Test Book',
            year: new BookYear(2024),
            isbn: new Isbn('9780132350884'),
            description: 'Test',
            coverUrl: null,
            authorIds: [1],
            published: false,
            version: 1
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

        $this->eventPublisher->expects($this->never())->method('publishEvent');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->useCase->execute($command);
    }
}
