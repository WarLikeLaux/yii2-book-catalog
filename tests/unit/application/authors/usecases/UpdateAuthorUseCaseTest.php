<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private UpdateAuthorUseCase $useCase;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->useCase = new UpdateAuthorUseCase($this->authorRepository);
    }

    public function testExecuteUpdatesAuthorSuccessfully(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'Новое ФИО');

        $existingAuthor = new AuthorReadDto(id: 42, fio: 'Старое ФИО');

        $this->authorRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('update')
            ->with(42, 'Новое ФИО');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorNotFound(): void
    {
        $command = new UpdateAuthorCommand(id: 999, fio: 'New Name');

        $this->authorRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->authorRepository->expects($this->never())->method('update');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Author not found');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsDomainExceptionOnRepositoryError(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'New Name');

        $existingAuthor = new AuthorReadDto(id: 42, fio: 'Old Name');

        $this->authorRepository->expects($this->once())
            ->method('findById')
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('update')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Failed to update author');

        $this->useCase->execute($command);
    }
}
