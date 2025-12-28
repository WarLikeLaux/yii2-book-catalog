<?php

declare(strict_types=1);

namespace app\presentation\services\books;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\UseCaseExecutor;
use app\application\ports\FileStorageInterface;
use app\presentation\forms\BookForm;
use app\presentation\mappers\BookFormMapper;
use Yii;
use yii\web\UploadedFile;

final readonly class BookCommandService
{
    public function __construct(
        private BookFormMapper $mapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private UseCaseExecutor $useCaseExecutor,
        private FileStorageInterface $fileStorage
    ) {
    }

    public function createBook(BookForm $form): ?int
    {
        $coverPath = $this->uploadCover($form);
        $command = $this->mapper->toCreateCommand($form, $coverPath);

        $bookId = null;
        $success = $this->useCaseExecutor->execute(function () use ($command, &$bookId): void {
            $bookId = $this->createBookUseCase->execute($command);
        }, Yii::t('app', 'Book has been created'));

        return $success ? $bookId : null;
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        $coverPath = $this->uploadCover($form);
        $command = $this->mapper->toUpdateCommand($id, $form, $coverPath);

        return $this->useCaseExecutor->execute(
            fn() => $this->updateBookUseCase->execute($command),
            Yii::t('app', 'Book has been updated'),
            ['book_id' => $id]
        );
    }

    public function deleteBook(int $id): bool
    {
        $command = new DeleteBookCommand($id);

        return $this->useCaseExecutor->execute(
            fn() => $this->deleteBookUseCase->execute($command),
            Yii::t('app', 'Book has been deleted'),
            ['book_id' => $id]
        );
    }

    /** @codeCoverageIgnore Делегирует в FileStorage, который покрыт отдельно */
    private function uploadCover(BookForm $form): ?string
    {
        if ($form->cover instanceof UploadedFile) {
            return $this->fileStorage->save($form->cover->tempName, $form->cover->extension);
        }
        return null;
    }
}
