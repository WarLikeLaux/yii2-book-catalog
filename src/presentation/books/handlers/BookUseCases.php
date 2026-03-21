<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\usecases\ChangeBookStatusUseCase;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;

final readonly class BookUseCases
{
    public function __construct(
        public CreateBookUseCase $create,
        public UpdateBookUseCase $update,
        public DeleteBookUseCase $delete,
        public ChangeBookStatusUseCase $changeStatus,
    ) {
    }
}
