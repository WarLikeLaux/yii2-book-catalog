<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;

/**
 * @implements UseCaseInterface<UpdateAuthorCommand, void>
 */
final readonly class UpdateAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
    ) {
    }

    /**
     * @param UpdateAuthorCommand $command
     */
    public function execute(object $command): void
    {
        if ($this->authorExistenceChecker->existsByFio($command->fio, $command->id)) {
            throw new AlreadyExistsException(DomainErrorCode::AuthorFioExists);
        }

        $author = $this->authorRepository->get($command->id);
        $author->update($command->fio);
        $this->authorRepository->save($author);
    }
}
