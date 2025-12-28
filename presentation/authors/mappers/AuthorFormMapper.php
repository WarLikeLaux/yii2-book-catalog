<?php

declare(strict_types=1);

namespace app\presentation\authors\mappers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\queries\AuthorReadDto;
use app\presentation\authors\forms\AuthorForm;

final class AuthorFormMapper
{
    public function toCreateCommand(AuthorForm $form): CreateAuthorCommand
    {
        return new CreateAuthorCommand(
            fio: $form->fio,
        );
    }

    public function toUpdateCommand(int $id, AuthorForm $form): UpdateAuthorCommand
    {
        return new UpdateAuthorCommand(
            id: $id,
            fio: $form->fio,
        );
    }

    public function toForm(AuthorReadDto $dto): AuthorForm
    {
        $form = new AuthorForm();
        $form->id = $dto->id;
        $form->fio = $dto->fio;

        return $form;
    }
}
