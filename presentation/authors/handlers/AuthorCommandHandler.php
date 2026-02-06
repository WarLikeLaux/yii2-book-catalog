<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\common\exceptions\OperationFailedException;
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
        $command = $this->operationRunner->runStep(
            fn(): CreateAuthorCommand => $this->commandMapper->toCreateCommand($form),
            'Failed to map author form to CreateAuthorCommand',
        );

        if ($command === null) {
            // Mapping failed (should theoretically throw exception in runStep if we want strictness,
            // but for now runStep returns null on error.
            // In BookCommandHandler we threw exception if mapping failed?
            // let's check BookCommandHandler logic.
            // BookCommandHandler uses runStep and checks for null.
            // But if runStep failed it logged error.
            // Here we should probably throw an exception if we want to catch it in controller.
            // Or assume runStep logs it and we just return null?
            // The contract says "Stop swallowing exceptions".
            // If runStep catches throwable and returns null, we swallow it here if we just return null (or fail type check).
            // Let's modify runStep behavior? No, runStep logic: "Logs error and returns null".
            // So if I return int, I MUST throw exception here if command is null.
            throw new OperationFailedException('error.internal_mapper_failed');
        }

        /** @var int */
        return $this->operationRunner->executeAndPropagate(
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
        );
    }

    public function updateAuthor(int $id, AuthorForm $form): void
    {
        $command = $this->operationRunner->runStep(
            fn(): UpdateAuthorCommand => $this->commandMapper->toUpdateCommand($id, $form),
            'Failed to map author form to UpdateAuthorCommand',
            ['author_id' => $id],
        );

        if ($command === null) {
            throw new OperationFailedException('error.internal_mapper_failed');
        }

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
