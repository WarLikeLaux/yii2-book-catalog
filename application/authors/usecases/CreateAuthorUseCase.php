<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use Throwable;

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
        try {
            $author = Author::create($command->fio);
            $this->authorRepository->save($author);

            return (int)$author->id;
        } catch (Throwable $e) {
            throw new OperationFailedException(DomainErrorCode::AuthorCreateFailed, 0, $e);
        }
    }
}
