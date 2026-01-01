<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\dto\TemporaryFile;
use app\application\ports\FileStorageInterface;
use app\domain\exceptions\DomainException;
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
            $this->fileStorage
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
            ->willReturnCallback(fn(callable $action) => $action());

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
    }

    public function testCreateBookMovesCoverAfterSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = new UploadedFile([
            'name' => 'test.jpg',
            'tempName' => '/tmp/test.jpg',
        ]);

        $command = $this->createMock(CreateBookCommand::class);
        $tempFile = new TemporaryFile('/tmp/test.jpg', '/uploads/test.jpg');

        $this->fileStorage->expects($this->once())
            ->method('saveTemporary')
            ->willReturn($tempFile);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, $tempFile->url)
            ->willReturn($command);

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn(7);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(fn(callable $action) => $action());

        $this->fileStorage->expects($this->once())
            ->method('moveToPermanent')
            ->with($tempFile);

        $result = $this->handler->createBook($form);

        $this->assertSame(7, $result);
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
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) use ($exception) {
                $onError();
                $onDomainError($exception);
                return null;
            });

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithDomainExceptionAndNullCoverDoesNotCallDelete(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $this->fileStorage->expects($this->never())
            ->method('deleteTemporary');

        $this->errorMapper->method('getFieldForError')->willReturn('isbn');

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) {
                $onError();
                $onDomainError(new DomainException('isbn.error.invalid_format'));
                return null;
            });

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);

        $command = $this->createMock(CreateBookCommand::class);
        $tempFile = new TemporaryFile('/tmp/test-cover.jpg', '/uploads/test-cover.jpg');

        $this->fileStorage->expects($this->once())
            ->method('saveTemporary')
            ->willReturn($tempFile);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $this->fileStorage->expects($this->once())
            ->method('deleteTemporary')
            ->with($tempFile);

        $this->errorMapper->method('getFieldForError')->willReturn('year');

        $form->cover = new UploadedFile([
            'name' => 'test.jpg',
            'tempName' => '/tmp/test.jpg',
        ]);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) {
                if ($onError !== null) {
                    $onError();
                }
                $onDomainError(new DomainException('year.error.too_old'));
                return null;
            });

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithUnknownErrorFallsBackToTitleField(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $exception = new DomainException('some.unknown.error');

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('some.unknown.error')
            ->willReturn(null);

        $form->expects($this->once())
            ->method('addError')
            ->with('title', $this->anything());

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) use ($exception) {
                $onError();
                $onDomainError($exception);
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
            ->willReturnCallback(fn(callable $action) => $action());

        $result = $this->handler->updateBook(123, $form);

        $this->assertTrue($result);
    }

    public function testUpdateBookWithDomainExceptionAddsFormError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willReturn($command);

        $exception = new DomainException('book.error.isbn_change_published');

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('book.error.isbn_change_published')
            ->willReturn('isbn');

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', $this->anything());

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) use ($exception) {
                $onError();
                $onDomainError($exception);
                return null;
            });

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookWithDomainExceptionAndNullCoverDoesNotCallDelete(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willReturn($command);

        $this->fileStorage->expects($this->never())
            ->method('deleteTemporary');

        $this->errorMapper->method('getFieldForError')->willReturn('isbn');

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) {
                $onError();
                $onDomainError(new DomainException('book.error.isbn_change_published'));
                return null;
            });

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);
        $tempFile = new TemporaryFile('/tmp/new-cover.jpg', '/uploads/new-cover.jpg');

        $this->fileStorage->expects($this->once())
            ->method('saveTemporary')
            ->willReturn($tempFile);

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willReturn($command);

        $this->fileStorage->expects($this->once())
            ->method('deleteTemporary')
            ->with($tempFile);

        $this->errorMapper->method('getFieldForError')->willReturn('title');

        $form->cover = new UploadedFile([
            'name' => 'test.jpg',
            'tempName' => '/tmp/test.jpg',
        ]);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(function ($action, $msg, $onDomainError, $onError) {
                if ($onError !== null) {
                    $onError();
                }
                $onDomainError(new DomainException('book.error.title_empty'));
                return null;
            });

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testPublishBookExecutesUseCase(): void
    {
        $bookId = 123;

        $this->useCaseRunner->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (callable $useCase, string $message, array $context): bool {
                $useCase();
                return true;
            });

        $this->publishBookUseCase->expects($this->once())
            ->method('execute');

        $result = $this->handler->publishBook($bookId);

        $this->assertTrue($result);
    }

    public function testDeleteBookExecutesUseCase(): void
    {
        $bookId = 456;

        $this->useCaseRunner->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (callable $useCase, string $message, array $context): bool {
                $useCase();
                return true;
            });

        $this->deleteBookUseCase->expects($this->once())
            ->method('execute');

        $result = $this->handler->deleteBook($bookId);

        $this->assertTrue($result);
    }
}
