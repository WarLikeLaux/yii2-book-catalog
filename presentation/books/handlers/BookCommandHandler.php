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

    public function createBook(BookForm $form): int|null
    {
        $coverRef = $this->processCoverUpload($form);

        $data = $form->toArray();
        $data['cover'] = $coverRef;
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;
        $data['authorIds'] = array_map(intval(...), (array)$form->authorIds);

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

    public function updateBook(int $id, BookForm $form): bool
    {
        $coverRef = $this->processCoverUpload($form);

        $data = $form->toArray();
        $data['id'] = $id;
        $data['cover'] = $coverRef;
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;
        $data['authorIds'] = array_map(intval(...), (array)$form->authorIds);

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
