<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\ports\UseCaseInterface;
use app\domain\repositories\AuthorRepositoryInterface;

/**
 * @implements UseCaseInterface<DeleteAuthorCommand, void>
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
    public function execute(object $command): void
    {
        $author = $this->authorRepository->get($command->id);

        $this->authorRepository->delete($author);
    }
}
