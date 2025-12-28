<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;

final readonly class CreateAuthorUseCase
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(CreateAuthorCommand $command): int
    {
        try {
            return $this->authorRepository->create($command->fio);
        } catch (\RuntimeException) {
            throw new DomainException('Failed to create author');
        }
    }
}
