<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;

final class UpdateAuthorUseCase
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(UpdateAuthorCommand $command): void
    {
        $author = $this->authorRepository->findById($command->id);
        if (!$author) {
            throw new DomainException('Author not found');
        }

        try {
            $this->authorRepository->update($command->id, $command->fio);
        } catch (\RuntimeException $e) {
            throw new DomainException('Failed to update author');
        }
    }
}
