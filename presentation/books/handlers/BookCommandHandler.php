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
use app\application\ports\ContentStorageInterface;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\handlers\UseCaseHandlerTrait;
use app\presentation\common\services\WebOperationRunner;
use Yii;
use yii\web\UploadedFile;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class BookCommandHandler
{
    use UseCaseHandlerTrait;

    public function __construct(
        private BookCommandMapper $commandMapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private PublishBookUseCase $publishBookUseCase,
        private WebOperationRunner $operationRunner,
        private ContentStorageInterface $contentStorage,
        private UploadedFileAdapter $uploadedFileAdapter,
    ) {
    }

    /**
     * @return array<string, string>
     */
    protected function getErrorFieldMap(): array
    {
        return [
            'isbn.error.invalid_format' => 'isbn',
            'book.error.isbn_exists' => 'isbn',
            'year.error.too_old' => 'year',
            'year.error.future' => 'year',
            'book.error.title_empty' => 'title',
            'book.error.title_too_long' => 'title',
            'book.error.stale_data' => 'version',
            'book.error.isbn_change_published' => 'isbn',
            'book.error.invalid_author_id' => 'authorIds',
            'book.error.publish_without_authors' => 'authorIds',
            'error.mapper_failed' => 'title',
        ];
    }

    public function createBook(BookForm $form): int|null
    {
        $cover = $this->operationRunner->runStep(
            fn(): ?StoredFileReference => $this->processCoverUpload($form),
            'Failed to upload book cover',
        );

        if ($form->cover instanceof UploadedFile && $cover === null) {
            $form->addError('cover', Yii::t('app', 'file.error.storage_operation_failed'));
            return null;
        }

        $command = $this->operationRunner->runStep(
            fn(): CreateBookCommand => $this->commandMapper->toCreateCommand($form, $cover),
            'Failed to map book form to CreateBookCommand',
        );

        if ($command === null) {
            $form->addError('title', Yii::t('app', 'error.internal_mapper_failed'));
            return null;
        }

        /** @var int|null */
        return $this->executeWithForm(
            $this->operationRunner,
            $form,
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
        );
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        $cover = $this->operationRunner->runStep(
            fn(): ?StoredFileReference => $this->processCoverUpload($form),
            'Failed to upload book cover',
            ['book_id' => $id],
        );

        if ($form->cover instanceof UploadedFile && $cover === null) {
            $form->addError('cover', Yii::t('app', 'file.error.storage_operation_failed'));
            return false;
        }

        $command = $this->operationRunner->runStep(
            fn(): UpdateBookCommand => $this->commandMapper->toUpdateCommand($id, $form, $cover),
            'Failed to map book form to UpdateBookCommand',
            ['book_id' => $id],
        );

        if ($command === null) {
            $form->addError('title', Yii::t('app', 'error.internal_mapper_failed'));
            return false;
        }

        return $this->executeWithForm(
            $this->operationRunner,
            $form,
            $command,
            $this->updateBookUseCase,
            Yii::t('app', 'book.success.updated'),
        ) !== null;
    }

    public function deleteBook(int $id): bool
    {
        $command = new DeleteBookCommand($id);

        $result = $this->operationRunner->execute(
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

        $result = $this->operationRunner->execute(
            $command,
            $this->publishBookUseCase,
            Yii::t('app', 'book.success.published'),
            ['book_id' => $id],
        );

        return (bool)$result;
    }

    private function processCoverUpload(BookForm $form): StoredFileReference|null
    {
        if (!$form->cover instanceof UploadedFile) {
            return null;
        }

        $fileContent = $this->uploadedFileAdapter->toFileContent($form->cover);
        $fileKey = $this->contentStorage->save($fileContent);
        return new StoredFileReference($fileKey->getExtendedPath($fileContent->extension));
    }
}
