<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;

/**
 * @implements UseCaseInterface<DeleteAuthorCommand, bool>
 */
final readonly class DeleteAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
    ) {
    }

    /**
     * @param DeleteAuthorCommand $command
     */
    public function execute(object $command): bool
    {
        $author = $this->authorRepository->get($command->id);

        $this->authorRepository->delete($author);

        return true;
    }
}
