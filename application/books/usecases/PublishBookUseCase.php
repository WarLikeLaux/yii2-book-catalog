<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\PublishBookCommand;
use app\application\common\exceptions\ApplicationException;
use app\application\common\services\TransactionalEventPublisher;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\domain\events\BookPublishedEvent;
use app\domain\exceptions\DomainException;
use app\domain\services\BookPublicationPolicy;

/**
 * @implements UseCaseInterface<PublishBookCommand, bool>
 */
final readonly class PublishBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionalEventPublisher $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    /**
     * @param PublishBookCommand $command
     */
    public function execute(object $command): bool
    {
        try {
            $book = $this->bookRepository->get($command->bookId);

            $book->publish($this->publicationPolicy);

            $this->bookRepository->save($book);

            $this->eventPublisher->publishAfterCommit(
                new BookPublishedEvent($command->bookId, $book->title, $book->year->value),
            );

            return true;
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
