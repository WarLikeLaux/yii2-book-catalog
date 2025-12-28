<?php

declare(strict_types=1);

namespace tests\unit\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\EntityNotFoundException;
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

        $existingAuthor = new Author(id: 42, fio: 'Старое ФИО');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Author $author) {
                return $author->getId() === 42 && $author->getFio() === 'Новое ФИО';
            }));

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenAuthorNotFound(): void
    {
        $command = new UpdateAuthorCommand(id: 999, fio: 'New Name');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('Author not found'));

        $this->authorRepository->expects($this->never())->method('save');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Author not found');

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsDomainExceptionOnRepositoryError(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'New Name');

        $existingAuthor = new Author(id: 42, fio: 'Old Name');

        $this->authorRepository->expects($this->once())
            ->method('get')
            ->willReturn($existingAuthor);

        $this->authorRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Failed to update author');

        $this->useCase->execute($command);
    }
}
