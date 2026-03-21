<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\exceptions\StorageErrorCode;
use app\domain\values\BookStatus;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\books\services\CoverUploadService;
use app\presentation\common\services\WebOperationRunner;
use Yii;
use yii\web\UploadedFile;

final readonly class BookCommandHandler
{
    public function __construct(
        private BookCommandMapper $commandMapper,
        private BookUseCases $useCases,
        private WebOperationRunner $operationRunner,
        private CoverUploadService $coverUploadService,
    ) {
    }

    public function createBook(BookForm $form): int
    {
        $cover = $this->operationRunner->runStep(
            fn(): ?string => $this->processCoverUpload($form),
            'Failed to upload book cover',
        );

        if ($form->cover instanceof UploadedFile && $cover === null) {
            throw new OperationFailedException(StorageErrorCode::OperationFailed->value, field: 'cover');
        }

        $command = $this->commandMapper->toCreateCommand($form, $cover);

        $result = $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->create,
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
            throw new OperationFailedException(StorageErrorCode::OperationFailed->value, field: 'cover');
        }

        $command = $this->commandMapper->toUpdateCommand($id, $form, $cover);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->update,
            Yii::t('app', 'book.success.updated'),
        );
    }

    public function deleteBook(int $id): void
    {
        $command = new DeleteBookCommand($id);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->delete,
            Yii::t('app', 'book.success.deleted'),
        );
    }

    public function changeBookStatus(int $id, BookStatus $targetStatus, string $successMessage): void
    {
        $command = new ChangeBookStatusCommand($id, $targetStatus);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->changeStatus,
            $successMessage,
        );
    }

    private function processCoverUpload(BookForm $form): string|null
    {
        if (!$form->cover instanceof UploadedFile) {
            return null;
        }

        return $this->coverUploadService->upload($form->cover);
    }
}
