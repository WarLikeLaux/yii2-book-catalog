<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\PublishBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\PublishBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\ports\FileStorageInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\mappers\DomainErrorToFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Psr\Log\LoggerInterface;
use Yii;
use yii\web\UploadedFile;

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
        private NotificationInterface $notifier,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    public function createBook(BookForm $form): int|null
    {
        $coverPath = $this->uploadCover($form);

        try {
            $command = $this->mapper->toCreateCommand($form, $coverPath);
            $bookId = $this->createBookUseCase->execute($command);
            $this->notifier->success(Yii::t('app', 'book.success.created'));
            return $bookId;
        } catch (DomainException $e) {
            $this->cleanupFile($coverPath);
            $this->addFormError($form, $e);
            return null;
        } catch (\Throwable $e) {
            $this->cleanupFile($coverPath);
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return null;
        }
    }

    public function updateBook(int $id, BookForm $form): bool
    {
        $coverPath = $this->uploadCover($form);

        try {
            $command = $this->mapper->toUpdateCommand($id, $form, $coverPath);
            $this->updateBookUseCase->execute($command);
            $this->notifier->success(Yii::t('app', 'book.success.updated'));
            return true;
        } catch (DomainException $e) {
            $this->cleanupFile($coverPath);
            $this->addFormError($form, $e);
            return false;
        } catch (\Throwable $e) {
            $this->cleanupFile($coverPath);
            $this->logger->error($e->getMessage(), ['exception' => $e, 'book_id' => $id]);
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return false;
        }
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
    private function uploadCover(BookForm $form): string|null
    {
        if ($form->cover instanceof UploadedFile) {
            return $this->fileStorage->save($form->cover->tempName, $form->cover->extension);
        }
        return null;
    }

    private function addFormError(BookForm $form, DomainException $e): void
    {
        $field = $this->errorMapper->getFieldForError($e->getMessage());
        $message = $this->translator->translate('app', $e->getMessage());

        $form->addError($field ?? 'title', $message);
    }

    private function cleanupFile(string|null $path): void
    {
        if ($path === null) {
            return;
        }

        $this->fileStorage->delete($path);
    }
}
