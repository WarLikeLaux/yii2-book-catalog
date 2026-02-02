<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\common\exceptions\ApplicationException;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\entities\Author;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\OperationFailedException;
use RuntimeException;

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
            try {
                $author = Author::create($command->fio);
                return $this->authorRepository->save($author);
            } catch (DomainException $exception) {
                throw $exception;
            } catch (RuntimeException $e) {
                throw new OperationFailedException(DomainErrorCode::AuthorCreateFailed, 0, $e);
            }
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
