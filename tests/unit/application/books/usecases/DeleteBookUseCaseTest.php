<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\queries\BookReadDto;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;
    private DeleteBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->useCase = new DeleteBookUseCase($this->bookRepository);
    }

    public function testExecuteDeletesBookSuccessfully(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = new BookReadDto(
            id: 42,
            title: 'Book to Delete',
            year: 2020,
            description: 'Description',
            isbn: '9780132350884',
            authorIds: [],
            authorNames: [],
            coverUrl: null
        );

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->with(42);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenBookNotFound(): void
    {
        $command = new DeleteBookCommand(id: 999);

        $this->bookRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->bookRepository->expects($this->never())->method('delete');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Book not found');

        $this->useCase->execute($command);
    }
}
