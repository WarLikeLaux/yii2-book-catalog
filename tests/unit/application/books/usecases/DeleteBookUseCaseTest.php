<?php

declare(strict_types=1);

namespace tests\unit\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\domain\entities\Book;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class DeleteBookUseCaseTest extends Unit
{
    private BookRepositoryInterface&MockObject $bookRepository;

    private CacheInterface&MockObject $cache;

    private DeleteBookUseCase $useCase;

    protected function _before(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->useCase = new DeleteBookUseCase($this->bookRepository, $this->cache);
    }

    public function testExecuteDeletesBookSuccessfully(): void
    {
        $command = new DeleteBookCommand(id: 42);

        $existingBook = new Book(
            id: 42,
            title: 'Book to Delete',
            year: new BookYear(2020),
            isbn: new Isbn('9780132350884'),
            description: 'Description',
            coverUrl: null,
            authorIds: [],
        );

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(42)
            ->willReturn($existingBook);

        $this->bookRepository->expects($this->once())
            ->method('delete')
            ->with($existingBook);

        $this->useCase->execute($command);
    }

    public function testExecuteThrowsExceptionWhenBookNotFound(): void
    {
        $command = new DeleteBookCommand(id: 999);

        $this->bookRepository->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('Book not found'));

        $this->bookRepository->expects($this->never())->method('delete');

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Book not found');

        $this->useCase->execute($command);
    }
}
