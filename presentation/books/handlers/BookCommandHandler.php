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
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\OperationFailedException;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\common\adapters\UploadedFileAdapter;
use app\presentation\common\handlers\UseCaseHandlerTrait;
use app\presentation\common\services\WebUseCaseRunner;
use AutoMapper\AutoMapperInterface;
use Psr\Log\LoggerInterface;
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
        private AutoMapperInterface $autoMapper,
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
        private PublishBookUseCase $publishBookUseCase,
        private WebUseCaseRunner $useCaseRunner,
        private ContentStorageInterface $contentStorage,
        private UploadedFileAdapter $uploadedFileAdapter,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, string>
     */
    protected function getErrorFieldMap(): array
    {
        return [
            'isbn.error.invalid_format' => 'isbn',
            'year.error.too_old' => 'year',
            'year.error.future' => 'year',
            'book.error.title_empty' => 'title',
            'book.error.title_too_long' => 'title',
            'book.error.isbn_change_published' => 'isbn',
            'book.error.invalid_author_id' => 'authorIds',
            'book.error.publish_without_authors' => 'authorIds',
        ];
    }

    public function createBook(BookForm $form): int|null
    {
        try {
            $cover = $this->processCoverUpload($form);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to upload book cover', ['exception' => $e]);
            $form->addError('cover', Yii::t('app', 'file.error.storage_operation_failed'));
            return null;
        }

        try {
            $data = $this->prepareCommandData($form, $cover);
            /** @var CreateBookCommand $command */
            $command = $this->autoMapper->map($data, CreateBookCommand::class);
        } catch (\Throwable $e) {
            $this->addFormError($form, $e instanceof DomainException ? $e : new OperationFailedException(DomainErrorCode::MapperFailed, 0, $e));
            return null;
        }

        /** @var int|null */
        return $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->createBookUseCase,
            Yii::t('app', 'book.success.created'),
        );
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        try {
            $cover = $this->processCoverUpload($form);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to upload book cover', ['exception' => $e, 'book_id' => $id]);
            $form->addError('cover', Yii::t('app', 'file.error.storage_operation_failed'));
            return false;
        }

        try {
            $data = $this->prepareCommandData($form, $cover);
            $data['id'] = $id;
            /** @var UpdateBookCommand $command */
            $command = $this->autoMapper->map($data, UpdateBookCommand::class);
        } catch (\Throwable $e) {
            $this->addFormError($form, $e instanceof DomainException ? $e : new OperationFailedException(DomainErrorCode::MapperFailed, 0, $e));
            return false;
        }

        return $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->updateBookUseCase,
            Yii::t('app', 'book.success.updated'),
        ) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareCommandData(BookForm $form, StoredFileReference|null $cover = null): array
    {
        $data = $form->toArray();
        $data['cover'] = $cover;
        $data['description'] = ($data['description'] ?? '') !== '' ? $data['description'] : null;

        $authorIds = (array)($form->authorIds ?? []);
        $data['authorIds'] = array_map(intval(...), array_filter($authorIds, is_numeric(...)));

        return $data;
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
