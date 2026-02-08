<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;

/**
 * @implements UseCaseInterface<UpdateAuthorCommand, void>
 */
final readonly class UpdateAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
    ) {
    }

    /**
     * @param UpdateAuthorCommand $command
     */
    public function execute(object $command): void
    {
        $author = $this->authorRepository->get($command->id);

        $author->update($command->fio);
        $this->authorRepository->save($author);
    }
}
