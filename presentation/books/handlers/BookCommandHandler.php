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
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\handlers\UseCaseHandlerTrait;
use app\presentation\common\services\WebUseCaseRunner;
use AutoMapper\AutoMapperInterface;
use Yii;
use yii\web\UploadedFile;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class BookCommandHandler
{
    use UseCaseHandlerTrait;

    /** @noRector \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateClassConstantRector */
    private const array ERROR_TO_FIELD_MAP = [
        'isbn.error.invalid_format' => 'isbn',
        'year.error.too_old' => 'year',
        'year.error.future' => 'year',
        'book.error.title_empty' => 'title',
        'book.error.title_too_long' => 'title',
        'book.error.isbn_change_published' => 'isbn',
        'book.error.invalid_author_id' => 'authorIds',
        'book.error.publish_without_authors' => 'authorIds',
    ];

    /**
     * Create a BookCommandHandler with required use cases, mapper, storage, and runner.
     *
     * @param AutoMapperInterface $autoMapper Maps arrays to command DTOs for use cases.
     * @param CreateBookUseCase $createBookUseCase Use case that handles creating books.
     * @param UpdateBookUseCase $updateBookUseCase Use case that handles updating books.
     * @param DeleteBookUseCase $deleteBookUseCase Use case that handles deleting books.
     * @param PublishBookUseCase $publishBookUseCase Use case that handles publishing books.
     * @param WebUseCaseRunner $useCaseRunner Executes use cases within a web request context.
     * @param ContentStorageInterface $contentStorage Persists uploaded file content and returns storage keys.
     * @param UploadedFileAdapter $uploadedFileAdapter Converts uploaded files into file content accepted by the content storage.
     */
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private PublishBookUseCase $publishBookUseCase,
        private WebUseCaseRunner $useCaseRunner,
        private ContentStorageInterface $contentStorage,
        private UploadedFileAdapter $uploadedFileAdapter,
    ) {
    }

    /**
     * Create a new book from the provided form data.
     *
     * @param BookForm $form Form containing book attributes and optional uploaded cover.
     * @return int|null The ID of the created book if successful, `null` otherwise.
     */
    public function createBook(BookForm $form): int|null
    {
        $data = $this->prepareCommandData($form);

        /** @var CreateBookCommand $command */
        $command = $this->autoMapper->map($data, CreateBookCommand::class);

        /** @var int|null $result */
        $result = $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
        );

        return $result;
    }

    /**
     * Update an existing book using data from the given form.
     *
     * @return bool `true` if the book was updated, `false` otherwise.
     */
    public function updateBook(int $id, BookForm $form): bool
    {
        $data = $this->prepareCommandData($form);
        $data['id'] = $id;

        /** @var UpdateBookCommand $command */
        $command = $this->autoMapper->map($data, UpdateBookCommand::class);

        $result = $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->updateBookUseCase,
            Yii::t('app', 'book.success.updated'),
        );

        return $result !== null;
    }

    /**
         * Prepare form data for creating or updating a book command payload.
         *
         * The returned array includes a processed `cover` (stored file reference or null),
         * `description` normalized to `null` when empty, and `authorIds` cast to integers.
         *
         * @param BookForm $form The submitted book form.
         * @return array<string,mixed> Prepared command data.
         */
    private function prepareCommandData(BookForm $form): array
    {
        $data = $form->toArray();
        $data['cover'] = $this->processCoverUpload($form);
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;
        $data['authorIds'] = array_map(intval(...), (array)$form->authorIds);

        return $data;
    }

    /**
     * Delete a book by its identifier.
     *
     * @param int $id The book identifier.
     * @return bool `true` if the book was deleted, `false` otherwise.
     */
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

    /**
     * Publish the book identified by the given id.
     *
     * @return bool `true` if the publish operation succeeded, `false` otherwise.
     */
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

    /**
     * Stores the uploaded cover file from the form and returns a reference to the stored file.
     *
     * @param BookForm $form The form containing the cover upload.
     * @return StoredFileReference|null A StoredFileReference for the saved cover (path includes extension), or `null` if no uploaded file is present on the form.
     */
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