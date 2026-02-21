<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

final class CreateAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;
    private AuthorQueryServiceInterface&MockObject $authorQueryService;
    private CreateAuthorUseCase $useCase;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->useCase = new CreateAuthorUseCase($this->authorRepository, $this->authorQueryService);
    }

    public function testExecuteCreatesAuthorSuccessfully(): void
    {
        $command = new CreateAuthorCommand(fio: 'Иванов Иван Иванович');

        $this->authorQueryService->expects($this->once())
            ->method('existsByFio')
            ->with('Иванов Иван Иванович')
            ->willReturn(false);

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (Author $author) {
                if ($author->fio !== 'Иванов Иван Иванович') {
                    return false;
                }

                $property = new ReflectionProperty(Author::class, 'id');
                $property->setValue($author, 42);

                return true;
            }))
            ->willReturn(42);

        $result = $this->useCase->execute($command);

        $this->assertSame(42, $result);
    }

    public function testExecuteThrowsAlreadyExistsExceptionWhenFioExists(): void
    {
        $command = new CreateAuthorCommand(fio: 'Duplicate FIO');

        $this->authorQueryService->expects($this->once())
            ->method('existsByFio')
            ->with('Duplicate FIO')
            ->willReturn(true);

        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorFioExists->value);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsRuntimeExceptionOnRepositoryError(): void
    {
        $command = new CreateAuthorCommand(fio: 'Test Author');

        $this->authorQueryService->method('existsByFio')->willReturn(false);
        $this->authorRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsDomainExceptionOnInvalidFio(): void
    {
        $command = new CreateAuthorCommand(fio: '');

        $this->authorQueryService->expects($this->once())
            ->method('existsByFio')
            ->with('')
            ->willReturn(false);

        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(DomainErrorCode::AuthorFioEmpty->value);

        $this->useCase->execute($command);
    }
}
