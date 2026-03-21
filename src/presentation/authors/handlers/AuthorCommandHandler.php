<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\DeleteAuthorCommand;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\services\WebOperationRunner;
use Yii;

final readonly class AuthorCommandHandler
{
    public function __construct(
        private AuthorCommandMapper $commandMapper,
        private AuthorUseCases $useCases,
        private WebOperationRunner $operationRunner,
    ) {
    }

    public function createAuthor(AuthorForm $form): int
    {
        $command = $this->commandMapper->toCreateCommand($form);

        /** @var int */
        return $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->create,
            Yii::t('app', 'author.success.created'),
        );
    }

    public function updateAuthor(int $id, AuthorForm $form): void
    {
        $command = $this->commandMapper->toUpdateCommand($id, $form);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->update,
            Yii::t('app', 'author.success.updated'),
        );
    }

    public function deleteAuthor(int $id): void
    {
        $command = new DeleteAuthorCommand($id);

        $this->operationRunner->executeAndPropagate(
            $command,
            $this->useCases->delete,
            Yii::t('app', 'author.success.deleted'),
        );
    }
}
