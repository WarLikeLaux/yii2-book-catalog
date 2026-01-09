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
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\services\WebUseCaseRunner;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\UploadedFile;

final class BookCommandHandlerTest extends Unit
{
    private const string TEST_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    private AutoMapperInterface&MockObject $autoMapper;
    private CreateBookUseCase&MockObject $createBookUseCase;
    private UpdateBookUseCase&MockObject $updateBookUseCase;
    private DeleteBookUseCase&MockObject $deleteBookUseCase;
    private PublishBookUseCase&MockObject $publishBookUseCase;
    private WebUseCaseRunner&MockObject $useCaseRunner;
    private ContentStorageInterface&MockObject $contentStorage;
    private UploadedFileAdapter&MockObject $uploadedFileAdapter;
    private BookCommandHandler $handler;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->createBookUseCase = $this->createMock(CreateBookUseCase::class);
        $this->updateBookUseCase = $this->createMock(UpdateBookUseCase::class);
        $this->deleteBookUseCase = $this->createMock(DeleteBookUseCase::class);
        $this->publishBookUseCase = $this->createMock(PublishBookUseCase::class);
        $this->useCaseRunner = $this->createMock(WebUseCaseRunner::class);
        $this->contentStorage = $this->createMock(ContentStorageInterface::class);
        $this->uploadedFileAdapter = $this->createMock(UploadedFileAdapter::class);

        $this->handler = new BookCommandHandler(
            $this->autoMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->publishBookUseCase,
            $this->useCaseRunner,
            $this->contentStorage,
            $this->uploadedFileAdapter,
        );
    }

    public function testCreateBookReturnsBookIdOnSuccess(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($this->callback(static fn($args) => $args['title'] === 'Test' && $args['cover'] === null), CreateBookCommand::class)
            ->willReturn($command);

        $this->mockUseCaseRunnerSimple(42);

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
    }

    public function testCreateBookSavesCoverToCas(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $createCommand = $this->createMock(CreateBookCommand::class);
        $this->mockContentStorageWithCover();

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($this->callback(static fn($args) => $args['title'] === 'Test' && $args['cover'] instanceof StoredFileReference), CreateBookCommand::class)
            ->willReturn($createCommand);

        $this->mockUseCaseRunnerSimple(7);

        $result = $this->handler->createBook($form);

        $this->assertSame(7, $result);
    }

    public function testCreateBookWithDomainExceptionReturnsNull(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->mockContentStorageWithCover();

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $command = $this->createMock(CreateBookCommand::class);
        $this->autoMapper->expects($this->once())->method('map')->willReturn($command);

        $this->mockUseCaseRunnerWithException();

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithDomainExceptionAddsFormError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);
        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->willReturn($command);

        $exception = new ValidationException(DomainErrorCode::IsbnInvalidFormat);

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', $this->anything());

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function ($_command, $_useCase, $_msg, $onDomainError, $_onError) use ($exception) {
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

        $command = $this->createMock(CreateBookCommand::class);
        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->willReturn($command);

        $exception = new ValidationException(DomainErrorCode::EntityAlreadyExists);

        $form->expects($this->once())->method('attributes')->willReturn(['title', 'description']);

        $form->expects($this->once())
            ->method('addError')
            ->with('title', $this->anything());

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function ($_command, $_useCase, $_msg, $onDomainError, $_onError) use ($exception) {
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

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($this->callback(static fn($args) => $args['title'] === 'Test' && $args['id'] === 123), UpdateBookCommand::class)
            ->willReturn($command);

        $this->mockUseCaseRunnerSimple(true);

        $result = $this->handler->updateBook(123, $form);

        $this->assertTrue($result);
    }

    public function testUpdateBookWithDomainExceptionReturnsFalse(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $this->mockContentStorageWithCover();

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $command = $this->createMock(UpdateBookCommand::class);
        $this->autoMapper->expects($this->once())->method('map')->willReturn($command);

        $this->mockUseCaseRunnerWithException();

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookSavesCoverToCas(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = $this->createUploadedFile();

        $updateCommand = $this->createMock(UpdateBookCommand::class);
        $this->mockContentStorageWithCover();

        $form->expects($this->once())->method('toArray')->willReturn(['title' => 'Test', 'description' => '']);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($this->callback(static fn($args) => $args['title'] === 'Test' && $args['id'] === 7 && $args['cover'] instanceof StoredFileReference), UpdateBookCommand::class)
            ->willReturn($updateCommand);

        $this->mockUseCaseRunnerSimple(true);

        $result = $this->handler->updateBook(7, $form);

        $this->assertTrue($result);
    }

    public function testPublishBookExecutesUseCase(): void
    {
        $bookId = 123;

        $this->useCaseRunner->expects($this->once())
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

        $this->useCaseRunner->expects($this->once())
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
        fwrite($stream, 'test content');
        rewind($stream);

        $fileContent = new FileContent($stream, 'jpg', 'image/jpeg');

        $fileKey = new FileKey(self::TEST_HASH);

        $this->uploadedFileAdapter->expects($this->once())
            ->method('toFileContent')
            ->willReturn($fileContent);

        $this->contentStorage->expects($this->once())
            ->method('save')
            ->with($fileContent)
            ->willReturn($fileKey);
    }

    private function mockUseCaseRunnerSimple(mixed $returnValue = null): void
    {
        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturn($returnValue);
    }

    private function mockUseCaseRunnerWithException(): void
    {
        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function ($_command, $_useCase, $_msg, $onDomainError, $_onError) {
                $onDomainError(new ValidationException(DomainErrorCode::BookTitleEmpty));
                return null;
            });
    }
}
