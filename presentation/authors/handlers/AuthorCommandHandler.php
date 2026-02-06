<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\common\exceptions\ApplicationException;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\services\WebOperationRunner;
use Yii;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class AuthorCommandHandler
{
    public function __construct(
        private AuthorCommandMapper $commandMapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private WebOperationRunner $operationRunner,
    ) {
    }

    public function createAuthor(AuthorForm $form): ?int
    {
        $command = $this->operationRunner->runStep(
            fn(): CreateAuthorCommand => $this->commandMapper->toCreateCommand($form),
            'Failed to map author form to CreateAuthorCommand',
        );

        if ($command === null) {
            $form->addError('fio', Yii::t('app', 'author.error.create_failed'));
            return null;
        }

        /** @var int|null */
        return $this->operationRunner->executeWithFormErrors(
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
            fn(ApplicationException $e) => $this->addFormError($form, $e),
        );
    }

    public function updateAuthor(int $id, AuthorForm $form): bool
    {
        $command = $this->operationRunner->runStep(
            fn(): UpdateAuthorCommand => $this->commandMapper->toUpdateCommand($id, $form),
            'Failed to map author form to UpdateAuthorCommand',
            ['author_id' => $id],
        );

        if ($command === null) {
            $form->addError('fio', Yii::t('app', 'author.error.update_failed'));
            return false;
        }

        return $this->operationRunner->executeWithFormErrors(
            $command,
            $this->updateAuthorUseCase,
            Yii::t('app', 'author.success.updated'),
            fn(ApplicationException $e) => $this->addFormError($form, $e),
        ) !== null;
    }

    public function deleteAuthor(int $id): bool
    {
        $command = new DeleteAuthorCommand($id);

        $result = $this->operationRunner->execute(
            $command,
            $this->deleteAuthorUseCase,
            Yii::t('app', 'author.success.deleted'),
            ['author_id' => $id],
        );

        return $result !== null;
    }

    private function addFormError(AuthorForm $form, ApplicationException $exception): void
    {
        $form->addError($this->resolveField($exception), Yii::t('app', $exception->errorCode));
    }

    private function resolveField(ApplicationException $exception): string
    {
        return match ($exception->errorCode) {
            'author.error.fio_exists',
            'author.error.fio_empty',
            'author.error.fio_too_short',
            'author.error.fio_too_long' => 'fio',
            default => '',
        };
    }
}
