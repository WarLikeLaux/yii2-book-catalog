<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\exceptions\DomainException;

final readonly class UpdateAuthorUseCase
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(UpdateAuthorCommand $command): void
    {
        $author = $this->authorRepository->get($command->id);

        try {
            $author->update($command->fio);
            $this->authorRepository->save($author);
        } catch (\RuntimeException) {
            throw new DomainException('author.error.update_failed');
        }
    }
}
