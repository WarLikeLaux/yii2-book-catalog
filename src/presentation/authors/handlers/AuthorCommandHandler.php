<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
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

    public function createAuthor(AuthorForm $form): int
    {
        $command = $this->commandMapper->toCreateCommand($form);

        /** @var int */
        return $this->operationRunner->executeAndPropagate(
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
        );
    }

    public function updateAuthor(int $id, AuthorForm $form): void
    {
        $command = $this->commandMapper->toUpdateCommand($id, $form);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->updateAuthorUseCase,
            Yii::t('app', 'author.success.updated'),
        );
    }

    public function deleteAuthor(int $id): void
    {
        $command = new DeleteAuthorCommand($id);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->deleteAuthorUseCase,
            Yii::t('app', 'author.success.deleted'),
        );
    }
}
