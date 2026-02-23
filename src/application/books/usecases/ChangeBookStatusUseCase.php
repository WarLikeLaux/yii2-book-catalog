<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\ChangeBookStatusCommand;
use app\application\ports\UseCaseInterface;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\services\BookPublicationPolicy;
use app\domain\values\BookStatus;

/**
 * @implements UseCaseInterface<ChangeBookStatusCommand, bool>
 */
final readonly class ChangeBookStatusUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    /**
     * @param ChangeBookStatusCommand $command
     */
    public function execute(object $command): bool
    {
        $book = $this->bookRepository->get($command->bookId);
        $policy = $command->targetStatus === BookStatus::Published ? $this->publicationPolicy : null;
        $book->transitionTo($command->targetStatus, $policy);

        $this->bookRepository->save($book);

        return true;
    }
}
