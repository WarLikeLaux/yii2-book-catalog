<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\PublishBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\dto\TemporaryFile;
use app\application\ports\FileStorageInterface;
use app\domain\exceptions\DomainException;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\mappers\DomainErrorToFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Yii;
use yii\web\UploadedFile;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "4. Группировка Handlers по сущностям")
 */
final readonly class BookCommandHandler
{
    public function __construct(
        private BookFormMapper $mapper,
        private DomainErrorToFormMapper $errorMapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private PublishBookUseCase $publishBookUseCase,
        private WebUseCaseRunner $useCaseRunner,
        private FileStorageInterface $fileStorage
    ) {
    }

    public function createBook(BookForm $form): int|null
    {
        $tempFile = $this->uploadCover($form);
        $permanentRef = $tempFile instanceof TemporaryFile ? $this->fileStorage->moveToPermanent($tempFile) : null;
        $command = $this->mapper->toCreateCommand($form, $permanentRef);

        return $this->useCaseRunner->executeWithFormErrors(
            fn(): int => $this->createBookUseCase->execute($command),
            Yii::t('app', 'book.success.created'),
            function (DomainException $e) use ($form, $permanentRef): void {
                if ($permanentRef instanceof StoredFileReference) {
                    $this->fileStorage->delete((string)$permanentRef);
                }

                $this->addFormError($form, $e);
            }
        );
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        $tempFile = $this->uploadCover($form);
        $permanentRef = $tempFile instanceof TemporaryFile ? $this->fileStorage->moveToPermanent($tempFile) : null;
        $command = $this->mapper->toUpdateCommand($id, $form, $permanentRef);

        return $this->useCaseRunner->executeWithFormErrors(
            function () use ($command): bool {
                $this->updateBookUseCase->execute($command);
                return true;
            },
            Yii::t('app', 'book.success.updated'),
            function (DomainException $e) use ($form, $permanentRef): void {
                if ($permanentRef instanceof StoredFileReference) {
                    $this->fileStorage->delete((string)$permanentRef);
                }

                $this->addFormError($form, $e);
            }
        ) ?? false;
    }

    public function deleteBook(int $id): bool
    {
        $command = new DeleteBookCommand($id);

        return $this->useCaseRunner->execute(
            fn() => $this->deleteBookUseCase->execute($command),
            Yii::t('app', 'book.success.deleted'),
            ['book_id' => $id]
        );
    }

    public function publishBook(int $id): bool
    {
        $command = new PublishBookCommand($id);

        return $this->useCaseRunner->execute(
            fn() => $this->publishBookUseCase->execute($command),
            Yii::t('app', 'book.success.published'),
            ['book_id' => $id]
        );
    }

    /** @codeCoverageIgnore Делегирует в FileStorage, который покрыт отдельно */
    private function uploadCover(BookForm $form): TemporaryFile|null
    {
        if ($form->cover instanceof UploadedFile) {
            return $this->fileStorage->saveTemporary($form->cover->tempName, $form->cover->extension);
        }

        return null;
    }

    private function addFormError(BookForm $form, DomainException $e): void
    {
        $field = $this->errorMapper->getFieldForError($e->getMessage());
        $message = Yii::t('app', $e->getMessage());

        $form->addError($field ?? 'title', $message);
    }
}
