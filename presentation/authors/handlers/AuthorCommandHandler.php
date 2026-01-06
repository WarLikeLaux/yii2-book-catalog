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
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class AuthorCommandHandler
{
    public function __construct(
        private AuthorFormMapper $mapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private WebUseCaseRunner $useCaseRunner,
    ) {
    }

    public function createAuthor(AuthorForm $form): ?int
    {
        $command = $this->mapper->toCreateCommand($form);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
        );

        /** @var int|null $result */
        return $result;
    }

    public function updateAuthor(int $id, AuthorForm $form): bool
    {
        $command = $this->mapper->toUpdateCommand($id, $form);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->updateAuthorUseCase,
            Yii::t('app', 'author.success.updated'),
            ['author_id' => $id],
        );

        return $result !== null;
    }

    public function deleteAuthor(int $id): bool
    {
        $command = new DeleteAuthorCommand($id);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->deleteAuthorUseCase,
            Yii::t('app', 'author.success.deleted'),
            ['author_id' => $id],
        );

        return $result !== null;
    }
}
