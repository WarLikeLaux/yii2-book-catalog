<?php

declare(strict_types=1);

namespace app\application\authors\mappers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchResponse;
use app\models\forms\AuthorForm;

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

    public function mapToSelect2(AuthorSearchResponse $response): array
    {
        return [
            'results' => array_map(fn(AuthorReadDto $dto) => [
                'id' => $dto->id,
                'text' => $dto->fio,
            ], $response->items),
            'pagination' => [
                'more' => ($response->page * $response->pageSize) < $response->total,
            ],
        ];
    }
}
