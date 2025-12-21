<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorReadDto;
use app\models\forms\AuthorForm;
use app\presentation\mappers\AuthorFormMapper;

final class AuthorFormPreparationService
{
    public function __construct(
        private readonly AuthorFormMapper $authorFormMapper
    ) {
    }

    public function prepareForUpdate(AuthorReadDto $dto): AuthorForm
    {
        return $this->authorFormMapper->toForm($dto);
    }
}
