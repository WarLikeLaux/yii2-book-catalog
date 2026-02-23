<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\ports\AuthorUsageCheckerInterface;
use app\domain\entities\Author;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\repositories\AuthorRepositoryInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private AuthorUsageCheckerInterface&MockObject $authorUsageChecker;
    private DeleteAuthorUseCase $useCase;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->authorUsageChecker = $this->createMock(AuthorUsageCheckerInterface::class);
        $this->useCase = new DeleteAuthorUseCase($this->authorRepository, $this->authorUsageChecker);
    }

    public function testExecuteDeletesAuthorSuccessfully(): void
    {
        $command = new DeleteAuthorCommand(id: 42);
        $existingAuthor = Author::reconstitute(id: 42, fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorUsageChecker->expects($this->once())
            ->method('hasSubscriptions')
            ->with(42)
            ->willReturn(false);

        $this->authorUsageChecker->expects($this->once())
            ->method('isLinkedToPublishedBooks')
            ->with(42)
            ->willReturn(false);

        $this->authorRepository->expects($this->once())
            ->method('delete')
            ->with($existingAuthor);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorNotFound(): void
    {
        $command = new DeleteAuthorCommand(id: 999);

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException(DomainErrorCode::AuthorNotFound));

        $this->authorUsageChecker->expects($this->never())->method('hasSubscriptions');
        $this->authorUsageChecker->expects($this->never())->method('isLinkedToPublishedBooks');
        $this->authorRepository->expects($this->never())->method('delete');

        $this->expectException(EntityNotFoundException::class);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorHasSubscriptions(): void
    {
        $command = new DeleteAuthorCommand(id: 42);
        $existingAuthor = Author::reconstitute(id: 42, fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorUsageChecker->expects($this->once())
            ->method('hasSubscriptions')
            ->with(42)
            ->willReturn(true);

        $this->authorUsageChecker->expects($this->never())->method('isLinkedToPublishedBooks');
        $this->authorRepository->expects($this->never())->method('removeAllBookLinks');
        $this->authorRepository->expects($this->never())->method('delete');

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('author.error.has_subscriptions');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorLinkedToPublishedBooks(): void
    {
        $command = new DeleteAuthorCommand(id: 42);
        $existingAuthor = Author::reconstitute(id: 42, fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorUsageChecker->expects($this->once())
            ->method('hasSubscriptions')
            ->with(42)
            ->willReturn(false);

        $this->authorUsageChecker->expects($this->once())
            ->method('isLinkedToPublishedBooks')
            ->with(42)
            ->willReturn(true);

        $this->authorRepository->expects($this->never())->method('removeAllBookLinks');
        $this->authorRepository->expects($this->never())->method('delete');

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('author.error.linked_to_published_books');

        $this->useCase->execute($command);
    }
}
