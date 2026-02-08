<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Author;

/**
 * @implements UseCaseInterface<CreateAuthorCommand, int>
 */
final readonly class CreateAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
    ) {
    }

    /**
     * @param CreateAuthorCommand $command
     */
    public function execute(object $command): int
    {
        $author = Author::create($command->fio);
        return $this->authorRepository->save($author);
    }
}
