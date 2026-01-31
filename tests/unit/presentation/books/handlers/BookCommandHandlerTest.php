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
use app\application\ports\ContentStorageInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\services\WebOperationRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\UploadedFile;

final class BookCommandHandlerTest extends Unit
{
    private const string TEST_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    private BookCommandMapper&MockObject $commandMapper;
    private CreateBookUseCase&MockObject $createBookUseCase;
    private UpdateBookUseCase&MockObject $updateBookUseCase;
    private DeleteBookUseCase&MockObject $deleteBookUseCase;
    private PublishBookUseCase&MockObject $publishBookUseCase;
    private WebOperationRunner&MockObject $operationRunner;
    private ContentStorageInterface&MockObject $contentStorage;
    private UploadedFileAdapter&MockObject $uploadedFileAdapter;
    private BookCommandHandler $handler;

    protected function _before(): void
    {
        $this->commandMapper = $this->createMock(BookCommandMapper::class);
        $this->createBookUseCase = $this->createMock(CreateBookUseCase::class);
        $this->updateBookUseCase = $this->createMock(UpdateBookUseCase::class);
        $this->deleteBookUseCase = $this->createMock(DeleteBookUseCase::class);
        $this->publishBookUseCase = $this->createMock(PublishBookUseCase::class);
        $this->operationRunner = $this->createMock(WebOperationRunner::class);
        $this->contentStorage = $this->createMock(ContentStorageInterface::class);
        $this->uploadedFileAdapter = $this->createMock(UploadedFileAdapter::class);

        $this->handler = new BookCommandHandler(
            $this->commandMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->publishBookUseCase,
            $this->operationRunner,
            $this->contentStorage,
            $this->uploadedFileAdapter,
        );
    }

    public function testCreateBookReturnsBookIdOnSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->operationRunner->expects($this->exactly(2))
            ->method('runStep')
            ->willReturnCallback(static fn($operation) => $operation());

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, null)
            ->willReturn($command);

        $this->mockOperationRunnerSimple(42);

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
    }

    public function testCreateBookSavesCoverToCas(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $createCommand = $this->createMock(CreateBookCommand::class);
        $coverRef = new StoredFileReference('path');

        $this->operationRunner->expects($this->exactly(2))
            ->method('runStep')
            ->willReturnCallback(static fn($operation) => $operation());

        $this->mockContentStorageWithCover();
        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form, $this->isInstanceOf(StoredFileReference::class))
            ->willReturn($createCommand);

        $this->mockOperationRunnerSimple(7);

        $result = $this->handler->createBook($form);

        $this->assertSame(7, $result);
    }

    public function testCreateBookWithDomainExceptionReturnsNull(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();
        $form->method('attributes')->willReturn(['title']);

        $this->mockContentStorageWithCover();

        $command = $this->createMock(CreateBookCommand::class);
        $this->commandMapper->expects($this->once())->method('toCreateCommand')->willReturn($command);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->mockOperationRunnerWithException();

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithDomainExceptionAddsFormError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;
        $form->method('attributes')->willReturn(['isbn']);

        $command = $this->createMock(CreateBookCommand::class);
        $this->commandMapper->expects($this->once())->method('toCreateCommand')->willReturn($command);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $exception = new ValidationException(DomainErrorCode::IsbnInvalidFormat);

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', $this->anything());

        $this->operationRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function (mixed $_, mixed $__, mixed $___, $onDomainError) use ($exception) {
                $onDomainError($exception);
                return null;
            });

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithUnknownDomainExceptionAddsDefaultFormError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;
        $form->method('attributes')->willReturn(['title', 'description']);

        $command = $this->createMock(CreateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toCreateCommand')->willReturn($command);

        $exception = new ValidationException(DomainErrorCode::EntityAlreadyExists);

        $form->expects($this->once())
            ->method('addError')
            ->with('title', $this->anything());

        $this->operationRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function (mixed $_, mixed $__, mixed $___, $onDomainError) use ($exception) {
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

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(123, $form, null)
            ->willReturn($command);

        $this->mockOperationRunnerSimple(true);

        $result = $this->handler->updateBook(123, $form);

        $this->assertTrue($result);
    }

    public function testUpdateBookWithDomainExceptionReturnsFalse(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();
        $form->method('attributes')->willReturn(['title']);

        $this->mockContentStorageWithCover();

        $command = $this->createMock(UpdateBookCommand::class);

        $this->operationRunner->method('runStep')->willReturnCallback(static fn($op) => $op());
        $this->commandMapper->expects($this->once())->method('toUpdateCommand')->willReturn($command);

        $this->mockOperationRunnerWithException();

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
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
            ->with(7, $form, $this->isInstanceOf(StoredFileReference::class))
            ->willReturn($updateCommand);

        $this->mockOperationRunnerSimple(true);

        $result = $this->handler->updateBook(7, $form);

        $this->assertTrue($result);
    }

    public function testCreateBookReturnsNullOnMappingError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        // runStep returns null if mapping failed (WebOperationRunner behavior simulated)
        $this->operationRunner->expects($this->exactly(2))
            ->method('runStep')
            ->willReturnOnConsecutiveCalls(null, null); // null for cover (okay), null for mapping (error)

        $form->expects($this->once())->method('addError')->with('title', $this->anything());

        $this->assertNull($this->handler->createBook($form));
    }

    public function testUpdateBookReturnsFalseOnMappingError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $this->operationRunner->expects($this->exactly(2))
            ->method('runStep')
            ->willReturnOnConsecutiveCalls(null, null);

        $form->expects($this->once())->method('addError')->with('title', $this->anything());

        $this->assertFalse($this->handler->updateBook(1, $form));
    }

    public function testProcessCoverUploadThrowsOnError(): void
    {
        $form = $this->createMock(BookForm::class);
        $file = $this->createUploadedFile();
        $form->cover = $file;

        // Start with runStep returning null (simulation of exception caught in runner)
        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $form->expects($this->once())->method('addError')->with('cover', $this->anything());

        $this->assertFalse($this->handler->updateBook(1, $form));
    }

    public function testCreateBookReturnsNullOnCoverUploadError(): void
    {
        $form = $this->createMock(BookForm::class);
        $file = $this->createUploadedFile();
        $form->cover = $file;

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $form->expects($this->once())->method('addError')->with('cover', $this->anything());

        $this->assertNull($this->handler->createBook($form));
    }

    public function testPublishBookExecutesUseCase(): void
    {
        $bookId = 123;

        $this->operationRunner->expects($this->once())
            ->method('execute')
            ->with(
                $this->isInstanceOf(PublishBookCommand::class),
                $this->isInstanceOf(PublishBookUseCase::class),
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(true);

        $result = $this->handler->publishBook($bookId);

        $this->assertTrue($result);
    }

    public function testDeleteBookExecutesUseCase(): void
    {
        $bookId = 456;

        $this->operationRunner->expects($this->once())
            ->method('execute')
            ->with(
                $this->isInstanceOf(DeleteBookCommand::class),
                $this->isInstanceOf(DeleteBookUseCase::class),
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(true);

        $result = $this->handler->deleteBook($bookId);

        $this->assertTrue($result);
    }

    public function testGetErrorFieldMapReturnsExpectedMap(): void
    {
        $map = $this->getErrorFieldMap($this->handler);

        $this->assertSame('isbn', $map['isbn.error.invalid_format']);
        $this->assertSame('isbn', $map['book.error.isbn_exists']);
        $this->assertSame('year', $map['year.error.too_old']);
        $this->assertSame('year', $map['year.error.future']);
        $this->assertSame('title', $map['book.error.title_empty']);
        $this->assertSame('title', $map['book.error.title_too_long']);
        $this->assertSame('version', $map['book.error.stale_data']);
        $this->assertSame('isbn', $map['book.error.isbn_change_published']);
        $this->assertSame('authorIds', $map['book.error.invalid_author_id']);
        $this->assertSame('authorIds', $map['book.error.publish_without_authors']);
        $this->assertSame('title', $map['error.mapper_failed']);
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
        $stream = fopen('php://memory', 'r+b');
        $this->assertIsResource($stream);

        fwrite($stream, 'test content');
        rewind($stream);

        $fileContent = new FileContent($stream, 'jpg', 'image/jpeg');
        $fileKey = new FileKey(self::TEST_HASH);

        $this->uploadedFileAdapter->expects($this->once())
            ->method('toFileContent')
            ->with($this->callback(static fn($arg) => $arg instanceof UploadedFile))
            ->willReturn($fileContent);

        $this->contentStorage->expects($this->once())
            ->method('save')
            ->with($this->equalTo($fileContent))
            ->willReturn($fileKey);
    }

    private function mockOperationRunnerSimple(mixed $returnValue = null): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturn($returnValue);
    }

    private function mockOperationRunnerWithException(): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function ($_command, $_useCase, $_msg, $onDomainError, $_onError) {
                $onDomainError(new ValidationException(DomainErrorCode::BookTitleEmpty));
                return null;
            });
    }

    /**
     * @return array<string, string>
     */
    private function getErrorFieldMap(BookCommandHandler $handler): array
    {
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('getErrorFieldMap');
        $method->setAccessible(true);

        /** @var array<string, string> $map */
        $map = $method->invoke($handler);

        return $map;
    }
}
