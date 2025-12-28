<?php

declare(strict_types=1);

namespace app\presentation\books\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\queries\BookReadDto;
use app\presentation\books\forms\BookForm;

final class BookFormMapper
{
    public function toCreateCommand(BookForm $form, ?string $coverPath): CreateBookCommand
    {
        return new CreateBookCommand(
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: $form->authorIds,
            cover: $coverPath,
        );
    }

    public function toUpdateCommand(int $id, BookForm $form, ?string $coverPath): UpdateBookCommand
    {
        return new UpdateBookCommand(
            id: $id,
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: $form->authorIds,
            cover: $coverPath,
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
