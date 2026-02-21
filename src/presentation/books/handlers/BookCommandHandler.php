<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\ChangeBookStatusUseCase;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\services\UploadedFileStorage;
use app\domain\values\BookStatus;
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
        private ChangeBookStatusUseCase $changeBookStatusUseCase,
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

        if ($form->cover instanceof UploadedFile && $cover === null) {
            throw new OperationFailedException('file.error.storage_operation_failed', field: 'cover');
        }

        $command = $this->commandMapper->toCreateCommand($form, $cover);

        $result = $this->operationRunner->executeAndPropagate(
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
        );
        assert(is_int($result));

        return $result;
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

        $command = $this->commandMapper->toUpdateCommand($id, $form, $cover);

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

    public function changeBookStatus(int $id, BookStatus $targetStatus, string $successMessage): void
    {
        $command = new ChangeBookStatusCommand($id, $targetStatus);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->changeBookStatusUseCase,
            $successMessage,
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
