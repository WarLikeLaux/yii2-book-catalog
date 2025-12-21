<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;

final class DeleteAuthorUseCase
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(DeleteAuthorCommand $command): void
    {
        $author = $this->authorRepository->findById($command->id);
        if (!$author) {
            throw new DomainException('Author not found');
        }

        $this->authorRepository->delete($command->id);
    }
}
