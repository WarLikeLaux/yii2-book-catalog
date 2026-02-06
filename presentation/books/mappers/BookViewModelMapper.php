<?php

declare(strict_types=1);

namespace app\presentation\books\mappers;

use app\application\books\queries\BookReadDto;
use app\presentation\books\dto\BookViewModel;

final class BookViewModelMapper
{
    public function map(BookReadDto $dto): BookViewModel
    {
        return new BookViewModel(
            $dto->id,
            $dto->title,
            $dto->year,
            $dto->description,
            $dto->isbn,
            $dto->authorNames,
            $dto->coverUrl,
            $dto->isPublished,
        );
    }
}
