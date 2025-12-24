<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\books\queries\BookReadDto;
use app\application\ports\FileStorageInterface;
use app\presentation\forms\BookForm;
use yii\web\UploadedFile;

final class BookFormMapper
{
    public function __construct(
        private readonly FileStorageInterface $fileStorage
    ) {
    }

    public function toCreateCommand(BookForm $form): CreateBookCommand
    {
        $coverPath = $this->processCover($form->cover);

        return new CreateBookCommand(
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: (array)$form->authorIds,
            cover: $coverPath,
        );
    }

    public function toUpdateCommand(int $id, BookForm $form): UpdateBookCommand
    {
        $coverPath = $this->processCover($form->cover);

        return new UpdateBookCommand(
            id: $id,
            title: $form->title,
            year: (int)$form->year,
            description: $form->description,
            isbn: $form->isbn,
            authorIds: (array)$form->authorIds,
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

    private function processCover(UploadedFile|string|null $cover): string|null
    {
        if ($cover instanceof UploadedFile) {
            return $this->fileStorage->save($cover);
        }

        if (is_string($cover) && $cover !== '') {
            return $cover;
        }

        return null;
    }
}
