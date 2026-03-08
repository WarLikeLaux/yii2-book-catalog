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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class UpdateAuthorUseCaseTest extends TestCase
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private UpdateAuthorUseCase $useCase;
    private AuthorExistenceCheckerInterface&Stub $authorExistenceChecker;

    protected function setUp(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->authorExistenceChecker = $this->createStub(AuthorExistenceCheckerInterface::class);
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository, $this->authorExistenceChecker);
    }

    public function testExecuteUpdatesAuthorSuccessfully(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'Новое ФИО');

        $authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $authorExistenceChecker->expects($this->once())
            ->method('existsByFio')
            ->with('Новое ФИО', 42)
            ->willReturn(false);
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository, $authorExistenceChecker);

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

        $authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $authorExistenceChecker->expects($this->never())
            ->method('existsByFio');
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository, $authorExistenceChecker);

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

        $authorExistenceChecker = $this->createMock(AuthorExistenceCheckerInterface::class);
        $authorExistenceChecker->expects($this->once())
            ->method('existsByFio')
            ->with('Duplicated Name', 42)
            ->willReturn(true);
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository, $authorExistenceChecker);

        $existingAuthor = Author::reconstitute(id: 42, fio: 'Duplicated Name');
        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorFioExists->value);

        $this->useCase->execute($command);
    }
}
