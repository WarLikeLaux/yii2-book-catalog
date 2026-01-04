<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\PublishBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\dto\TemporaryFile;
use app\application\ports\FileStorageInterface;
use app\domain\exceptions\DomainException;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\mappers\DomainErrorToFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\UploadedFile;

final class BookCommandHandlerTest extends Unit
{
    private BookFormMapper&MockObject $mapper;
    private DomainErrorToFormMapper&MockObject $errorMapper;
    private CreateBookUseCase&MockObject $createBookUseCase;
    private UpdateBookUseCase&MockObject $updateBookUseCase;
    private DeleteBookUseCase&MockObject $deleteBookUseCase;
    private PublishBookUseCase&MockObject $publishBookUseCase;
    private WebUseCaseRunner&MockObject $useCaseRunner;
    private FileStorageInterface&MockObject $fileStorage;
    private BookCommandHandler $handler;

    protected function _before(): void
    {
        $this->mapper = $this->createMock(BookFormMapper::class);
        $this->errorMapper = $this->createMock(DomainErrorToFormMapper::class);
        $this->createBookUseCase = $this->createMock(CreateBookUseCase::class);
        $this->updateBookUseCase = $this->createMock(UpdateBookUseCase::class);
        $this->deleteBookUseCase = $this->createMock(DeleteBookUseCase::class);
        $this->publishBookUseCase = $this->createMock(PublishBookUseCase::class);
        $this->useCaseRunner = $this->createMock(WebUseCaseRunner::class);
        $this->fileStorage = $this->createMock(FileStorageInterface::class);

        $this->handler = new BookCommandHandler(
            $this->mapper,
            $this->errorMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->publishBookUseCase,
            $this->useCaseRunner,
            $this->fileStorage,
        );
    }

    public function testCreateBookReturnsBookIdOnSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, null)
            ->willReturn($command);

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn(42);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static fn(callable $action) => $action());

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
    }

    public function testCreateBookMovesCoverAfterSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $createCommand = $this->createMock(CreateBookCommand::class);
        $ref = $this->mockFileStorageWithCover();

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, $ref)
            ->willReturn($createCommand);

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->with($createCommand)
            ->willReturn(7);

        $this->mockUseCaseRunnerSimple();

        $result = $this->handler->createBook($form);

        $this->assertSame(7, $result);
    }

    public function testCreateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        [$tempFile, $ref] = $this->createStorageFile();
        $this->fileStorage->method('saveTemporary')->willReturn($tempFile);
        $this->fileStorage->method('moveToPermanent')->willReturn($ref);

        $this->createBookUseCase->method('execute')
            ->willThrowException(new DomainException('error'));

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with((string)$ref);

        $this->mockUseCaseRunnerWithException();

        $this->handler->createBook($form);
    }

    public function testCreateBookWithDomainExceptionAddsFormError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $exception = new DomainException('isbn.error.invalid_format');

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('isbn.error.invalid_format')
            ->willReturn('isbn');

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', $this->anything());

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function ($action, $msg, $onError) use ($exception) {
                $onError($exception);
                return null;
            });

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testUpdateBookReturnsTrueOnSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(123, $form, null)
            ->willReturn($command);

        $this->updateBookUseCase->expects($this->once())
            ->method('execute')
            ->with($command);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static fn(callable $action) => $action());

        $result = $this->handler->updateBook(123, $form);

        $this->assertTrue($result);
    }

    public function testUpdateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        [$tempFile, $ref] = $this->createStorageFile();
        $this->fileStorage->method('saveTemporary')->willReturn($tempFile);
        $this->fileStorage->method('moveToPermanent')->willReturn($ref);

        $this->updateBookUseCase->method('execute')
            ->willThrowException(new DomainException('error'));

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with((string)$ref);

        $this->mockUseCaseRunnerWithException();

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookMovesCoverAfterSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $updateCommand = $this->createMock(UpdateBookCommand::class);
        $ref = $this->mockFileStorageWithCover();

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(7, $form, $ref)
            ->willReturn($updateCommand);

        $this->updateBookUseCase->expects($this->once())
            ->method('execute')
            ->with($updateCommand);

        $this->mockUseCaseRunnerSimple();

        $result = $this->handler->updateBook(7, $form);

        $this->assertTrue($result);
    }

    public function testPublishBookExecutesUseCase(): void
    {
        $bookId = 123;

        $this->useCaseRunner->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static function (callable $useCase, string $message, array $context): bool {
                $useCase();
                return true;
            });

        $this->publishBookUseCase->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(PublishBookCommand::class));

        $result = $this->handler->publishBook($bookId);

        $this->assertTrue($result);
    }

    public function testDeleteBookExecutesUseCase(): void
    {
        $bookId = 456;

        $this->useCaseRunner->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static function (callable $useCase, string $message, array $context): bool {
                $useCase();
                return true;
            });

        $this->deleteBookUseCase->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(DeleteBookCommand::class));

        $result = $this->handler->deleteBook($bookId);

        $this->assertTrue($result);
    }

    private function createUploadedFile(string $name = 'test.jpg', string $tempPath = '/tmp/test.jpg'): UploadedFile
    {
        return new UploadedFile([
            'name' => $name,
            'tempName' => $tempPath,
        ]);
    }

    private function createStorageFile(string $tempPath = '/tmp/test.jpg', string $name = 'test.jpg'): array
    {
        $tempFile = new TemporaryFile($tempPath, $name);
        $ref = new StoredFileReference($name);
        return [$tempFile, $ref];
    }

    private function mockFileStorageWithCover(string $tempPath = '/tmp/test.jpg', string $name = 'test.jpg'): StoredFileReference
    {
        [$tempFile, $ref] = $this->createStorageFile($tempPath, $name);

        $this->fileStorage->expects($this->once())
            ->method('saveTemporary')
            ->willReturn($tempFile);

        $this->fileStorage->expects($this->once())
            ->method('moveToPermanent')
            ->with($tempFile)
            ->willReturn($ref);

        return $ref;
    }

    private function mockUseCaseRunnerSimple(): void
    {
        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static fn(callable $action) => $action());
    }

    private function mockUseCaseRunnerWithException(): void
    {
        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function (callable $action, string $msg, callable $onError) {
                try {
                    return $action();
                } catch (DomainException $e) {
                    $onError($e);
                    return null;
                }
            });
    }
}
