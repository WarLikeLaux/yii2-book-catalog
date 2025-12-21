<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\queries\BookReadDto;
use app\models\forms\BookForm;

final class BookFormMapper
{
    public function toCreateCommand(BookForm $form): CreateBookCommand
    {
        return new CreateBookCommand(
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: (array)$form->authorIds,
            cover: $form->cover,
        );
    }

    public function toUpdateCommand(int $id, BookForm $form): UpdateBookCommand
    {
        return new UpdateBookCommand(
            id: $id,
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: (array)$form->authorIds,
            cover: $form->cover,
        );
    }

    public function toForm(BookReadDto $dto): BookForm
    {
        $form = new BookForm();
        $form->id = $dto->id;
        $form->title = $dto->title;
        $form->year = $dto->year;
        $form->description = $dto->description;
        $form->isbn = $dto->isbn;
        $form->authorIds = $dto->authorIds;

        return $form;
    }
}
