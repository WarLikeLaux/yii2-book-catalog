<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\ChangeBookStatusUseCase;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\dto\UploadedFilePayload;
use app\application\common\exceptions\ApplicationException;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\services\UploadedFileStorage;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\services\WebOperationRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use yii\web\UploadedFile;

final class BookCommandHandlerTest extends Unit
{
    private const COVER_PATH = 'covers/test.jpg';
    private const MSG_MAPPER_FAILED = 'mapper failed';
    private BookCommandMapper&MockObject $commandMapper;
    private CreateBookUseCase&MockObject $createBookUseCase;
    private UpdateBookUseCase&MockObject $updateBookUseCase;
    private DeleteBookUseCase&MockObject $deleteBookUseCase;
    private ChangeBookStatusUseCase&MockObject $changeBookStatusUseCase;
    private WebOperationRunner&MockObject $operationRunner;
    private UploadedFileStorage&MockObject $uploadedFileStorage;
    private UploadedFileAdapter&MockObject $uploadedFileAdapter;
    private BookCommandHandler $handler;

    protected function _before(): void
    {
        $this->commandMapper = $this->createMock(BookCommandMapper::class);
        $this->createBookUseCase = $this->createMock(CreateBookUseCase::class);
        $this->updateBookUseCase = $this->createMock(UpdateBookUseCase::class);
        $this->deleteBookUseCase = $this->createMock(DeleteBookUseCase::class);
        $this->changeBookStatusUseCase = $this->createMock(ChangeBookStatusUseCase::class);
        $this->operationRunner = $this->createMock(WebOperationRunner::class);
        $this->uploadedFileStorage = $this->createMock(UploadedFileStorage::class);
        $this->uploadedFileAdapter = $this->createMock(UploadedFileAdapter::class);

        $this->handler = new BookCommandHandler(
            $this->commandMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->changeBookStatusUseCase,
            $this->operationRunner,
            $this->uploadedFileStorage,
            $this->uploadedFileAdapter,
        );
    }

    public function testCreateBookReturnsBookIdOnSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturnCallback(static fn($operation) => $operation());

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, null)
            ->willReturn($command);

        $this->mockOperationRunnerExecute(42);

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
    }

    public function testCreateBookSavesCoverToCas(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $createCommand = $this->createMock(CreateBookCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturnCallback(static fn($operation) => $operation());

        $this->mockContentStorageWithCover();
        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, self::COVER_PATH)
            ->willReturn($createCommand);

        $this->mockOperationRunnerExecute(7);

        $result = $this->handler->createBook($form);

        $this->assertSame(7, $result);
    }

    public function testCreateBookPropagatesApplicationException(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toCreateCommand')->willReturn($command);

        $this->expectException(ApplicationException::class);
        $this->mockOperationRunnerExecuteWithException();

        $this->handler->createBook($form);
    }

    public function testCreateBookPropagatesMapperException(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $this->handler->createBook($form);
    }

    public function testUpdateBookSucceeds(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(123, $form, null)
            ->willReturn($command);

        $this->mockOperationRunnerExecute(true);

        $this->handler->updateBook(123, $form);
    }

    public function testUpdateBookPropagatesException(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toUpdateCommand')->willReturn($command);

        $this->expectException(ApplicationException::class);
        $this->mockOperationRunnerExecuteWithException();

        $this->handler->updateBook(123, $form);
    }

    public function testUpdateBookSavesCoverToCas(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $updateCommand = $this->createMock(UpdateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->mockContentStorageWithCover();
        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(7, $form, self::COVER_PATH)
            ->willReturn($updateCommand);

        $this->mockOperationRunnerExecute(true);

        $this->handler->updateBook(7, $form);
    }

    public function testUpdateBookPropagatesMapperException(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $this->handler->updateBook(1, $form);
    }

    public function testUpdateBookThrowsOnCoverUploadError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);

        $this->handler->updateBook(1, $form);
    }

    public function testCreateBookThrowsOnCoverUploadError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);

        $this->handler->createBook($form);
    }

    public function testChangeBookStatusExecutesUseCase(): void
    {
        $bookId = 123;

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->with(
                $this->isInstanceOf(ChangeBookStatusCommand::class),
                $this->isInstanceOf(ChangeBookStatusUseCase::class),
                $this->anything(),
            )
            ->willReturn(true);

        $this->handler->changeBookStatus($bookId, 'published', 'Published!');
    }

    public function testDeleteBookExecutesUseCase(): void
    {
        $bookId = 456;

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->with(
                $this->isInstanceOf(DeleteBookCommand::class),
                $this->isInstanceOf(DeleteBookUseCase::class),
                $this->anything(),
            )
            ->willReturn(true);

        $this->handler->deleteBook($bookId);
    }

    private function createUploadedFile(string $name = 'test.jpg', string $tempPath = '/tmp/test.jpg'): UploadedFile
    {
        return new UploadedFile([
            'name' => $name,
            'tempName' => $tempPath,
        ]);
    }

    private function mockContentStorageWithCover(): void
    {
        $payload = new UploadedFilePayload('/tmp/test.jpg', 'jpg', 'image/jpeg');

        $this->uploadedFileAdapter->expects($this->once())
            ->method('toPayload')
            ->with($this->callback(static fn($arg) => $arg instanceof UploadedFile))
            ->willReturn($payload);

        $this->uploadedFileStorage->expects($this->once())
            ->method('store')
            ->with($this->equalTo($payload))
            ->willReturn(self::COVER_PATH);
    }

    private function mockOperationRunnerExecute(mixed $returnValue = null): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->willReturn($returnValue);
    }

    private function mockOperationRunnerExecuteWithException(): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->willThrowException(new ApplicationException('book.error.title_empty'));
    }
}
