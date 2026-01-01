<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateAuthorUseCaseTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $authorRepository;

    private CreateAuthorUseCase $useCase;

    protected function _before(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepositoryInterface::class);
        $this->useCase = new CreateAuthorUseCase($this->authorRepository);
    }

    public function testExecuteCreatesAuthorSuccessfully(): void
    {
        $command = new CreateAuthorCommand(fio: 'Иванов Иван Иванович');

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Author $author) {
                if ($author->getFio() !== 'Иванов Иван Иванович') {
                    return false;
                }
                $author->setId(42);
                return true;
            }));

        $result = $this->useCase->execute($command);

        $this->assertSame(42, $result);
    }

    public function testExecuteThrowsDomainExceptionOnRepositoryError(): void
    {
        $command = new CreateAuthorCommand(fio: 'Test Author');

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.create_failed');

        $this->useCase->execute($command);
    }
}
