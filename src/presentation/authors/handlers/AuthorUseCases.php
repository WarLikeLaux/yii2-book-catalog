<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;

final readonly class AuthorUseCases
{
    public function __construct(
        public CreateAuthorUseCase $create,
        public UpdateAuthorUseCase $update,
        public DeleteAuthorUseCase $delete,
    ) {
    }
}
