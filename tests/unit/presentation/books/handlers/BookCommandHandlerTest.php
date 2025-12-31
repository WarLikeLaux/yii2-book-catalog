<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\ports\FileStorageInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\mappers\DomainErrorToFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
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

    private NotificationInterface&MockObject $notifier;

    private TranslatorInterface&MockObject $translator;

    private LoggerInterface&MockObject $logger;

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
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new BookCommandHandler(
            $this->mapper,
            $this->errorMapper,
            $this->createBookUseCase,
            $this->updateBookUseCase,
            $this->deleteBookUseCase,
            $this->publishBookUseCase,
            $this->useCaseRunner,
            $this->fileStorage,
            $this->notifier,
            $this->translator,
            $this->logger
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

        $this->notifier->expects($this->once())
            ->method('success');

        $result = $this->handler->createBook($form);

        $this->assertSame(42, $result);
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

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('isbn.error.invalid_format')
            ->willReturn('isbn');

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'isbn.error.invalid_format')
            ->willReturn('Invalid ISBN format');

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', 'Invalid ISBN format');

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
    }

    public function testCreateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);

        $command = $this->createMock(CreateBookCommand::class);
        $coverPath = '/uploads/test-cover.jpg';

        $this->fileStorage->expects($this->once())
            ->method('save')
            ->willReturn($coverPath);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $exception = new DomainException('year.error.too_old');

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with($coverPath);

        $this->errorMapper->method('getFieldForError')->willReturn('year');
        $this->translator->method('translate')->willReturn('Year is too old');

        $form->cover = new UploadedFile([
            'name' => 'test.jpg',
            'tempName' => '/tmp/test.jpg',
        ]);

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

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('some.unknown.error')
            ->willReturn(null);

        $this->translator->expects($this->once())
            ->method('translate')
            ->willReturn('Unknown error');

        $form->expects($this->once())
            ->method('addError')
            ->with('title', 'Unknown error');

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

        $this->notifier->expects($this->once())
            ->method('success');

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

        $this->updateBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->errorMapper->expects($this->once())
            ->method('getFieldForError')
            ->with('book.error.isbn_change_published')
            ->willReturn('isbn');

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'book.error.isbn_change_published')
            ->willReturn('Cannot change ISBN of published book');

        $form->expects($this->once())
            ->method('addError')
            ->with('isbn', 'Cannot change ISBN of published book');

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookWithDomainExceptionCleansUpFile(): void
    {
        $form = $this->createMock(BookForm::class);
        $coverPath = '/uploads/new-cover.jpg';

        $this->fileStorage->expects($this->once())
            ->method('save')
            ->willReturn($coverPath);

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willReturn($command);

        $exception = new DomainException('book.error.title_empty');

        $this->updateBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->fileStorage->expects($this->once())
            ->method('delete')
            ->with($coverPath);

        $this->errorMapper->method('getFieldForError')->willReturn('title');
        $this->translator->method('translate')->willReturn('Title is empty');

        $form->cover = new UploadedFile([
            'name' => 'test.jpg',
            'tempName' => '/tmp/test.jpg',
        ]);

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testUpdateBookWithUnexpectedExceptionLogsAndNotifiesError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(UpdateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willReturn($command);

        $exception = new \RuntimeException('Database connection failed');

        $this->updateBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Database connection failed', $this->arrayHasKey('exception'));

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'error.unexpected')
            ->willReturn('Unexpected error occurred');

        $this->notifier->expects($this->once())
            ->method('error')
            ->with('Unexpected error occurred');

        $result = $this->handler->updateBook(123, $form);

        $this->assertFalse($result);
    }

    public function testCreateBookWithUnexpectedExceptionLogsAndNotifiesError(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->cover = null;

        $command = $this->createMock(CreateBookCommand::class);

        $this->mapper->expects($this->once())
            ->method('toCreateCommand')
            ->willReturn($command);

        $exception = new \RuntimeException('Database connection failed');

        $this->createBookUseCase->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Database connection failed', $this->arrayHasKey('exception'));

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'error.unexpected')
            ->willReturn('Unexpected error occurred');

        $this->notifier->expects($this->once())
            ->method('error')
            ->with('Unexpected error occurred');

        $result = $this->handler->createBook($form);

        $this->assertNull($result);
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
