<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\PublishBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\exceptions\ApplicationException;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\services\UploadedFileStorage;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\services\WebOperationRunner;
use Yii;
use yii\web\UploadedFile;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class BookCommandHandler
{
    public function __construct(
        private BookCommandMapper $commandMapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private PublishBookUseCase $publishBookUseCase,
        private WebOperationRunner $operationRunner,
        private UploadedFileStorage $uploadedFileStorage,
        private UploadedFileAdapter $uploadedFileAdapter,
    ) {
    }

    public function createBook(BookForm $form): int
    {
        $cover = $this->operationRunner->runStep(
            fn(): ?string => $this->processCoverUpload($form),
            'Failed to upload book cover',
        );

        // runStep catches exceptions and returns null, but we want to fail hard if upload fails?
        // Actually runStep swallows. But processCoverUpload MUST return null if no file.
        // If file exists but upload failed... processCoverUpload logic below relies on adapter/storage which might throw.
        // Wait, runStep catches Throwable. So if upload throws, cover is null.
        // But if form has file, and cover is null -> error.
        if ($form->cover instanceof UploadedFile && $cover === null) {
            throw new OperationFailedException('file.error.storage_operation_failed', field: 'cover');
        }

        $command = $this->operationRunner->runStep(
            fn(): CreateBookCommand => $this->commandMapper->toCreateCommand($form, $cover),
            'Failed to map book form to CreateBookCommand',
        );

        if ($command === null) {
            throw new ApplicationException('error.internal_mapper_failed');
        }

        /** @var int */
        return $this->operationRunner->executeAndPropagate(
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
        );
    }

    public function updateBook(int $id, BookForm $form): void
    {
        $cover = $this->operationRunner->runStep(
            fn(): ?string => $this->processCoverUpload($form),
            'Failed to upload book cover',
            ['book_id' => $id],
        );

        if ($form->cover instanceof UploadedFile && $cover === null) {
            throw new OperationFailedException('file.error.storage_operation_failed', field: 'cover');
        }

        $command = $this->operationRunner->runStep(
            fn(): UpdateBookCommand => $this->commandMapper->toUpdateCommand($id, $form, $cover),
            'Failed to map book form to UpdateBookCommand',
            ['book_id' => $id],
        );

        if ($command === null) {
            // Using generic exception or Maybe create BookUpdateException?
            // Reusing BookCreationException formapper failure is slightly confusing but acceptable for now or generic ApplicationException
            throw new ApplicationException('error.internal_mapper_failed');
        }

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->updateBookUseCase,
            Yii::t('app', 'book.success.updated'),
        );
    }

    public function deleteBook(int $id): void
    {
        $command = new DeleteBookCommand($id);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->deleteBookUseCase,
            Yii::t('app', 'book.success.deleted'),
        );
    }

    public function publishBook(int $id): void
    {
        $command = new PublishBookCommand($id);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->publishBookUseCase,
            Yii::t('app', 'book.success.published'),
        );
    }

    private function processCoverUpload(BookForm $form): string|null
    {
        if (!$form->cover instanceof UploadedFile) {
            return null;
        }

        $payload = $this->uploadedFileAdapter->toPayload($form->cover);
        return $this->uploadedFileStorage->store($payload);
    }
}
