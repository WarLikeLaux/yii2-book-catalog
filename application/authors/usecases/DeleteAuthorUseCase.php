<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;

final readonly class DeleteAuthorUseCase
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(DeleteAuthorCommand $command): void
    {
        $author = $this->authorRepository->get($command->id);

        $this->authorRepository->delete($author);
    }
}
