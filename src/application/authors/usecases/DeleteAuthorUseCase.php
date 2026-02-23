<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\ports\AuthorUsageCheckerInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\repositories\AuthorRepositoryInterface;

/**
 * @implements UseCaseInterface<DeleteAuthorCommand, void>
 */
final readonly class DeleteAuthorUseCase implements UseCaseInterface
{
    public function __construct(
        private AuthorRepositoryInterface $authorRepository,
        private AuthorUsageCheckerInterface $authorUsageChecker,
    ) {
    }

    /**
     * @param DeleteAuthorCommand $command
     */
    public function execute(object $command): void
    {
        $author = $this->authorRepository->get($command->id);

        if ($this->authorUsageChecker->hasSubscriptions($command->id)) {
            throw new BusinessRuleException(DomainErrorCode::AuthorHasSubscriptions);
        }

        if ($this->authorUsageChecker->isLinkedToPublishedBooks($command->id)) {
            throw new BusinessRuleException(DomainErrorCode::AuthorLinkedToPublishedBooks);
        }

        $this->authorRepository->delete($author);
    }
}
