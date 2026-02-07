<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\books\usecases\ChangeBookStatusUseCase;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\domain\events\BookStatusChangedEvent;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookStatus;
use BookTestHelper;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ChangeBookStatusUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private TransactionalEventPublisher&MockObject $eventPublisher;
    private ChangeBookStatusUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->eventPublisher = $this->createMock(TransactionalEventPublisher::class);

        $this->useCase = new ChangeBookStatusUseCase(
            $this->bookRepository,
            $this->eventPublisher,
            new BookPublicationPolicy(),
        );
    }

    public function testPublishBookSuccessfully(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            description: 'This is a valid description that is long enough to pass the minimum requirement of 50 characters.',
            coverImage: 'covers/test.jpg',
            authorIds: [1],
            status: BookStatus::Draft,
        );

        $this->bookRepository->method('get')->willReturn($book);
        $this->bookRepository->expects($this->once())->method('save');

        $this->eventPublisher->expects($this->once())->method('publishAfterCommit')
            ->with($this->callback(static fn(object $event): bool => $event instanceof BookStatusChangedEvent
                && $event->bookId === 1
                && $event->oldStatus === BookStatus::Draft
                && $event->newStatus === BookStatus::Published
                && $event->year === 2024));

        $result = $this->useCase->execute(new ChangeBookStatusCommand(1, 'published'));

        $this->assertTrue($result);
        $this->assertSame(BookStatus::Published, $book->status);
    }

    public function testUnpublishBookSuccessfully(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            authorIds: [1],
            status: BookStatus::Published,
        );

        $this->bookRepository->method('get')->willReturn($book);
        $this->bookRepository->expects($this->once())->method('save');
        $this->eventPublisher->expects($this->once())->method('publishAfterCommit');

        $result = $this->useCase->execute(new ChangeBookStatusCommand(1, 'draft'));

        $this->assertTrue($result);
        $this->assertSame(BookStatus::Draft, $book->status);
    }

    public function testArchiveBookSuccessfully(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            authorIds: [1],
            status: BookStatus::Published,
        );

        $this->bookRepository->method('get')->willReturn($book);
        $this->bookRepository->expects($this->once())->method('save');
        $this->eventPublisher->expects($this->once())->method('publishAfterCommit');

        $result = $this->useCase->execute(new ChangeBookStatusCommand(1, 'archived'));

        $this->assertTrue($result);
        $this->assertSame(BookStatus::Archived, $book->status);
    }

    public function testRestoreArchivedBookSuccessfully(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            authorIds: [1],
            status: BookStatus::Archived,
        );

        $this->bookRepository->method('get')->willReturn($book);
        $this->bookRepository->expects($this->once())->method('save');

        $result = $this->useCase->execute(new ChangeBookStatusCommand(1, 'draft'));

        $this->assertTrue($result);
        $this->assertSame(BookStatus::Draft, $book->status);
    }

    public function testDraftToArchivedForbidden(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            status: BookStatus::Draft,
        );

        $this->bookRepository->method('get')->willReturn($book);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_status_transition');

        $this->useCase->execute(new ChangeBookStatusCommand(1, 'archived'));
    }

    public function testArchivedToPublishedForbidden(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            status: BookStatus::Archived,
        );

        $this->bookRepository->method('get')->willReturn($book);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_status_transition');

        $this->useCase->execute(new ChangeBookStatusCommand(1, 'published'));
    }

    public function testInvalidStatusStringThrows(): void
    {
        $book = BookTestHelper::createBook(
            id: 1,
            status: BookStatus::Draft,
        );

        $this->bookRepository->method('get')->willReturn($book);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.invalid_status_transition');

        $this->useCase->execute(new ChangeBookStatusCommand(1, 'garbage'));
    }
}
