<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainException;
use Throwable;

final readonly class CreateAuthorUseCase
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository
    ) {
    }

    public function execute(CreateAuthorCommand $command): int
    {
        try {
            $author = Author::create($command->fio);
            $this->authorRepository->save($author);

            return (int)$author->id;
        } catch (Throwable $e) {
            throw new DomainException('author.error.create_failed', 0, $e);
        }
    }
}
