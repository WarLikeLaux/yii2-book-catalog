<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\queries\BookReadDto;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;
use app\domain\exceptions\DomainException;

final readonly class DeleteBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private CacheInterface $cache,
    ) {
    }

    public function execute(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->findById($command->id);
        if (!$book instanceof BookReadDto) {
            throw new DomainException('Book not found');
        }

        $year = $book->year;

        $this->bookRepository->delete($command->id);

        $this->cache->delete(sprintf('report:top_authors:%d', $year));
    }
}
