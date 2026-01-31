<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\handlers\UseCaseHandlerTrait;
use app\presentation\common\services\WebOperationRunner;
use Yii;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class AuthorCommandHandler
{
    use UseCaseHandlerTrait;

    public function __construct(
        private AuthorCommandMapper $commandMapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private WebOperationRunner $operationRunner,
    ) {
    }

    /**
     * @return array<string, string>
     */
    protected function getErrorFieldMap(): array
    {
        return [
            'author.error.fio_exists' => 'fio',
            'author.error.fio_empty' => 'fio',
            'author.error.fio_too_short' => 'fio',
            'author.error.fio_too_long' => 'fio',
        ];
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
        return $this->executeWithForm(
            $this->operationRunner,
            $form,
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
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

        return $this->executeWithForm(
            $this->operationRunner,
            $form,
            $command,
            $this->updateAuthorUseCase,
            Yii::t('app', 'author.success.updated'),
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
}
