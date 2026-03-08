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
use app\domain\values\BookStatus;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\services\WebOperationRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii\web\UploadedFile;

final class BookCommandHandlerTest extends TestCase
{
    private const COVER_PATH = 'covers/test.jpg';
    private const MSG_MAPPER_FAILED = 'mapper failed';
    private BookCommandMapper&MockObject $commandMapper;
    private CreateBookUseCase&Stub $createBookUseCase;
    private UpdateBookUseCase&Stub $updateBookUseCase;
    private DeleteBookUseCase&Stub $deleteBookUseCase;
    private ChangeBookStatusUseCase&Stub $changeBookStatusUseCase;
    private WebOperationRunner&MockObject $operationRunner;
    private UploadedFileStorage&Stub $uploadedFileStorage;
    private UploadedFileAdapter&Stub $uploadedFileAdapter;
    private BookCommandHandler $handler;

    protected function setUp(): void
    {
        $this->commandMapper = $this->createMock(BookCommandMapper::class);
        $this->createBookUseCase = $this->createStub(CreateBookUseCase::class);
        $this->updateBookUseCase = $this->createStub(UpdateBookUseCase::class);
        $this->deleteBookUseCase = $this->createStub(DeleteBookUseCase::class);
        $this->changeBookStatusUseCase = $this->createStub(ChangeBookStatusUseCase::class);
        $this->operationRunner = $this->createMock(WebOperationRunner::class);
        $this->uploadedFileStorage = $this->createStub(UploadedFileStorage::class);
        $this->uploadedFileAdapter = $this->createStub(UploadedFileAdapter::class);

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
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $command = $this->createStub(CreateBookCommand::class);

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
        $form = $this->createStub(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $createCommand = $this->createStub(CreateBookCommand::class);

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
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $command = $this->createStub(CreateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toCreateCommand')->willReturn($command);

        $this->expectException(ApplicationException::class);
        $this->mockOperationRunnerExecuteWithException();

        $this->handler->createBook($form);
    }

    public function testCreateBookPropagatesMapperException(): void
    {
        $this->operationRunner->expects($this->never())->method($this->anything());
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $operationRunner = $this->createStub(WebOperationRunner::class);
        $operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $handler = new BookCommandHandler(
            $this->commandMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->changeBookStatusUseCase,
            $operationRunner,
            $this->uploadedFileStorage,
            $this->uploadedFileAdapter,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $handler->createBook($form);
    }

    public function testUpdateBookSucceeds(): void
    {
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $command = $this->createStub(UpdateBookCommand::class);

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
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $command = $this->createStub(UpdateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toUpdateCommand')->willReturn($command);

        $this->expectException(ApplicationException::class);
        $this->mockOperationRunnerExecuteWithException();

        $this->handler->updateBook(123, $form);
    }

    public function testUpdateBookSavesCoverToCas(): void
    {
        $form = $this->createStub(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $updateCommand = $this->createStub(UpdateBookCommand::class);

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
        $this->operationRunner->expects($this->never())->method($this->anything());
        $form = $this->createStub(BookForm::class);
        $form->cover = null;

        $operationRunner = $this->createStub(WebOperationRunner::class);
        $operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $handler = new BookCommandHandler(
            $this->commandMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->changeBookStatusUseCase,
            $operationRunner,
            $this->uploadedFileStorage,
            $this->uploadedFileAdapter,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $handler->updateBook(1, $form);
    }

    public function testUpdateBookThrowsOnCoverUploadError(): void
    {
        $this->commandMapper->expects($this->never())->method($this->anything());
        $form = $this->createStub(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);

        $this->createHandlerWithStubMapper()->updateBook(1, $form);
    }

    public function testCreateBookThrowsOnCoverUploadError(): void
    {
        $this->commandMapper->expects($this->never())->method($this->anything());
        $form = $this->createStub(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);

        $this->createHandlerWithStubMapper()->createBook($form);
    }

    public function testChangeBookStatusExecutesUseCase(): void
    {
        $this->commandMapper->expects($this->never())->method($this->anything());
        $bookId = 123;

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->with(
                $this->isInstanceOf(ChangeBookStatusCommand::class),
                $this->isInstanceOf(ChangeBookStatusUseCase::class),
                $this->anything(),
            )
            ->willReturn(true);

        $this->createHandlerWithStubMapper()->changeBookStatus($bookId, BookStatus::Published, 'Published!');
    }

    public function testDeleteBookExecutesUseCase(): void
    {
        $this->commandMapper->expects($this->never())->method($this->anything());
        $bookId = 456;

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->with(
                $this->isInstanceOf(DeleteBookCommand::class),
                $this->isInstanceOf(DeleteBookUseCase::class),
                $this->anything(),
            )
            ->willReturn(true);

        $this->createHandlerWithStubMapper()->deleteBook($bookId);
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

        $this->uploadedFileAdapter
            ->method('toPayload')
            ->willReturn($payload);

        $this->uploadedFileStorage
            ->method('store')
            ->willReturn(self::COVER_PATH);
    }

    private function createHandlerWithStubMapper(): BookCommandHandler
    {
        return new BookCommandHandler(
            $this->createStub(BookCommandMapper::class),
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->changeBookStatusUseCase,
            $this->operationRunner,
            $this->uploadedFileStorage,
            $this->uploadedFileAdapter,
        );
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
