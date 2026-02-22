<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\domain\entities\Author;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\repositories\AuthorRepositoryInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private UpdateAuthorUseCase $useCase;
    private AuthorExistenceCheckerInterface&MockObject $authorExistenceChecker;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository, $this->authorExistenceChecker);
    }

    public function testExecuteUpdatesAuthorSuccessfully(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'Новое ФИО');

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsByFio')
            ->with('Новое ФИО', 42)
            ->willReturn(false);

        $existingAuthor = Author::reconstitute(id: 42, fio: 'Старое ФИО');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Author $author) => $author->id === 42 && $author->fio === 'Новое ФИО'))
            ->willReturn(42);

        $this->useCase->execute($command);

        $this->assertTrue(true);
    }

    public function testExecuteThrowsEntityNotFoundExceptionWhenAuthorNotFound(): void
    {
        $command = new UpdateAuthorCommand(id: 999, fio: 'New Name');

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsByFio')
            ->with('New Name', 999)
            ->willReturn(false);

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException(DomainErrorCode::AuthorNotFound));

        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorNotFound->value);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsRuntimeExceptionOnRepositoryError(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'New Name');

        $this->authorExistenceChecker->method('existsByFio')->willReturn(false);
        $existingAuthor = Author::reconstitute(id: 42, fio: 'Old Name');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsAlreadyExistsExceptionWhenFioExists(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'Duplicated Name');

        $this->authorExistenceChecker->expects($this->once())
            ->method('existsByFio')
            ->with('Duplicated Name', 42)
            ->willReturn(true);

        $this->authorRepository->expects($this->never())->method('get');
        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorFioExists->value);

        $this->useCase->execute($command);
    }
}
