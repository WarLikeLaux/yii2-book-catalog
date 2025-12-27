<?php

declare(strict_types=1);

namespace app\presentation\services\authors;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\common\UseCaseExecutor;
use app\presentation\forms\AuthorForm;
use app\presentation\mappers\AuthorFormMapper;
use Yii;

final readonly class AuthorCommandService
{
    public function __construct(
        private AuthorFormMapper $mapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private UseCaseExecutor $useCaseExecutor
    ) {
    }

    public function createAuthor(AuthorForm $form): ?int
    {
        $command = $this->mapper->toCreateCommand($form);

        $authorId = null;
        $success = $this->useCaseExecutor->execute(function () use ($command, &$authorId): void {
            $authorId = $this->createAuthorUseCase->execute($command);
        }, Yii::t('app', 'Author has been created'));

        return $success ? $authorId : null;
    }

    public function updateAuthor(int $id, AuthorForm $form): bool
    {
        $command = $this->mapper->toUpdateCommand($id, $form);

        return $this->useCaseExecutor->execute(
            fn() => $this->updateAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been updated'),
            ['author_id' => $id]
        );
    }

    public function deleteAuthor(int $id): bool
    {
        $command = new DeleteAuthorCommand($id);

        return $this->useCaseExecutor->execute(
            fn() => $this->deleteAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been deleted'),
            ['author_id' => $id]
        );
    }
}
