<?php

declare(strict_types=1);

namespace app\presentation\authors\mappers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\common\mappers\AutoMapperContextBuilder;
use AutoMapper\AutoMapperInterface;

final readonly class AuthorCommandMapper
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private AutoMapperContextBuilder $contextBuilder,
    ) {
    }

    public function toCreateCommand(AuthorForm $form): CreateAuthorCommand
    {
        /** @var CreateAuthorCommand $command */
        $command = $this->autoMapper->map(
            source: $form,
            target: CreateAuthorCommand::class,
        );

        return $command;
    }

    public function toUpdateCommand(int $id, AuthorForm $form): UpdateAuthorCommand
    {
        /** @var UpdateAuthorCommand $command */
        $command = $this->autoMapper->map(
            source: $form,
            target: UpdateAuthorCommand::class,
            context: $this->contextBuilder->build([
                UpdateAuthorCommand::class => ['id' => $id],
            ]),
        );

        return $command;
    }
}
