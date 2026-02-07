<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\events\BookStatusChangedEvent;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookStatus;

/**
 * @implements UseCaseInterface<ChangeBookStatusCommand, bool>
 */
final readonly class ChangeBookStatusUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionalEventPublisher $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    /**
     * @param ChangeBookStatusCommand $command
     */
    public function execute(object $command): bool
    {
        $book = $this->bookRepository->get($command->bookId);
        $oldStatus = $book->status;
        $targetStatus = BookStatus::tryFrom($command->targetStatus)
        ?? throw new BusinessRuleException(DomainErrorCode::BookInvalidStatusTransition);

        $policy = $targetStatus === BookStatus::Published ? $this->publicationPolicy : null;
        $book->transitionTo($targetStatus, $policy);

        $this->bookRepository->save($book);

        $this->eventPublisher->publishAfterCommit(
            new BookStatusChangedEvent($command->bookId, $oldStatus, $targetStatus),
        );

        return true;
    }
}
