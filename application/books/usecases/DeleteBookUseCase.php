<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\CacheInterface;

final readonly class DeleteBookUseCase
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private CacheInterface $cache,
    ) {
    }

    public function execute(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->get($command->id);

        $year = $book->getYear()->value;

        $this->bookRepository->delete($book);

        $this->cache->delete(sprintf('report:top_authors:%d', $year));
    }
}
