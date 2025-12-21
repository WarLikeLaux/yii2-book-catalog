<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\models\forms\AuthorForm;
use app\presentation\mappers\AuthorFormMapper;

final class AuthorFormPreparationService
{
    public function __construct(
        private readonly AuthorFormMapper $authorFormMapper,
        private readonly AuthorQueryService $authorQueryService
    ) {
    }

    public function prepareForUpdate(AuthorReadDto $dto): AuthorForm
    {
        return $this->authorFormMapper->toForm($dto);
    }

    public function prepareUpdateForm(int $id): AuthorForm
    {
        $dto = $this->authorQueryService->getById($id);
        return $this->authorFormMapper->toForm($dto);
    }
}
