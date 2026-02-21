<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Author;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;

/**
 * @implements UseCaseInterface<CreateAuthorCommand, int>
 */
final readonly class CreateAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
        private AuthorQueryServiceInterface $authorQueryService,
    ) {
    }

    /**
     * @param CreateAuthorCommand $command
     */
    public function execute(object $command): int
    {
        if ($this->authorQueryService->existsByFio($command->fio)) {
            throw new AlreadyExistsException(DomainErrorCode::AuthorFioExists);
        }

        $author = Author::create($command->fio);
        return $this->authorRepository->save($author);
    }
}
