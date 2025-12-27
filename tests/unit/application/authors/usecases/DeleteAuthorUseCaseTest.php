<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private DeleteAuthorUseCase $useCase;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->useCase = new DeleteAuthorUseCase($this->authorRepository);
    }

    public function testExecuteDeletesAuthorSuccessfully(): void
    {
        $command = new DeleteAuthorCommand(id: 42);

        $existingAuthor = new AuthorReadDto(id: 42, fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('delete')
            ->with(42);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorNotFound(): void
    {
        $command = new DeleteAuthorCommand(id: 999);

        $this->authorRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->authorRepository->expects($this->never())->method('delete');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Author not found');

        $this->useCase->execute($command);
    }
}
