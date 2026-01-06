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
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\mappers\DomainErrorToFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Yii;
use yii\web\UploadedFile;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
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
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function createBook(BookForm $form): int|null
    {
        $tempFile = $this->uploadCover($form);
        $permanentRef = $tempFile instanceof TemporaryFile ? $this->fileStorage->moveToPermanent($tempFile) : null;
        $command = $this->mapper->toCreateCommand($form, $permanentRef);

        $result = $this->useCaseRunner->executeWithFormErrors(
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
            function (DomainException $e) use ($form): void {
                $this->addFormError($form, $e);
            },
        );

        /** @var int|null $result */
        return $result;
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        $tempFile = $this->uploadCover($form);
        $permanentRef = $tempFile instanceof TemporaryFile ? $this->fileStorage->moveToPermanent($tempFile) : null;
        $command = $this->mapper->toUpdateCommand($id, $form, $permanentRef);
        return (bool) $this->useCaseRunner->executeWithFormErrors(
            $command,
            $this->updateBookUseCase,
            Yii::t('app', 'book.success.updated'),
            function (DomainException $e) use ($form): void {
                $this->addFormError($form, $e);
            },
        );
    }

    public function deleteBook(int $id): bool
    {
        $command = new DeleteBookCommand($id);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->deleteBookUseCase,
            Yii::t('app', 'book.success.deleted'),
            ['book_id' => $id],
        );

        return (bool)$result;
    }

    public function publishBook(int $id): bool
    {
        $command = new PublishBookCommand($id);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->publishBookUseCase,
            Yii::t('app', 'book.success.published'),
            ['book_id' => $id],
        );

        return (bool)$result;
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
