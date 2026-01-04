<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorFormMapper;
use app\presentation\common\services\WebUseCaseRunner;
use Yii;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "4. Группировка Handlers по сущностям")
 */
final readonly class AuthorCommandHandler
{
    public function __construct(
        private AuthorFormMapper $mapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private WebUseCaseRunner $useCaseRunner
    ) {
    }

    public function createAuthor(AuthorForm $form): ?int
    {
        $command = $this->mapper->toCreateCommand($form);

        $authorId = null;
        $success = $this->useCaseRunner->execute(function () use ($command, &$authorId): void {
            $authorId = $this->createAuthorUseCase->execute($command);
        }, Yii::t('app', 'author.success.created'));

        return $success ? $authorId : null;
    }

    public function updateAuthor(int $id, AuthorForm $form): bool
    {
        $command = $this->mapper->toUpdateCommand($id, $form);

        return $this->useCaseRunner->execute(
            fn() => $this->updateAuthorUseCase->execute($command),
            Yii::t('app', 'author.success.updated'),
            ['author_id' => $id]
        );
    }

    public function deleteAuthor(int $id): bool
    {
        $command = new DeleteAuthorCommand($id);

        return $this->useCaseRunner->execute(
            fn() => $this->deleteAuthorUseCase->execute($command),
            Yii::t('app', 'author.success.deleted'),
            ['author_id' => $id]
        );
    }
}
