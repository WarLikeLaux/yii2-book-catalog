<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\books\queries\BookReadDto;
use app\models\forms\BookForm;
use app\presentation\mappers\BookFormMapper;

final class BookFormPreparationService
{
    public function __construct(
        private readonly BookFormMapper $bookFormMapper
    ) {
    }

    public function prepareForUpdate(BookReadDto $dto): BookForm
    {
        return $this->bookFormMapper->toForm($dto);
    }
}
