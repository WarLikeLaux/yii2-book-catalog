<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\common\exceptions\ApplicationException;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
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

        $existingAuthor = new Author(id: 42, fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('delete')
            ->with($existingAuthor);

        $result = $this->useCase->execute($command);

        $this->assertTrue($result);
    }

    public function testExecuteThrowsExceptionWhenAuthorNotFound(): void
    {
        $command = new DeleteAuthorCommand(id: 999);

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException(DomainErrorCode::AuthorNotFound));

        $this->authorRepository->expects($this->never())->method('delete');

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorNotFound->value);

        $this->useCase->execute($command);
    }
}
