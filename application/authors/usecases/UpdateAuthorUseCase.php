<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\application\common\exceptions\ApplicationException;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\OperationFailedException;
use RuntimeException;

/**
 * @implements UseCaseInterface<UpdateAuthorCommand, bool>
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
    public function execute(object $command): bool
    {
        try {
            $author = $this->authorRepository->get($command->id);

            try {
                $author->update($command->fio);
                $this->authorRepository->save($author);

                return true;
            } catch (DomainException $exception) {
                throw $exception;
            } catch (RuntimeException $e) {
                throw new OperationFailedException(DomainErrorCode::AuthorUpdateFailed, 0, $e);
            }
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
