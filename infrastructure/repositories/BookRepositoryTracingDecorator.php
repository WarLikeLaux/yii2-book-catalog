<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\application\ports\TracerInterface;
use app\domain\entities\Book;

final readonly class BookRepositoryTracingDecorator implements BookRepositoryInterface
{
    public function __construct(
        private BookRepositoryInterface $repository,
        private TracerInterface $tracer
    ) {
    }

    #[\Override]
    public function save(Book $book): void
    {
        $this->tracer->trace('BookRepo::' . __FUNCTION__, fn() => $this->repository->save($book));
    }

    #[\Override]
    public function get(int $id): Book
    {
        return $this->tracer->trace('BookRepo::' . __FUNCTION__, fn(): Book => $this->repository->get($id));
    }

    #[\Override]
    public function delete(Book $book): void
    {
        $this->tracer->trace('BookRepo::' . __FUNCTION__, fn() => $this->repository->delete($book));
    }

    #[\Override]
    public function findById(int $id): ?BookReadDto
    {
        return $this->tracer->trace('BookRepo::' . __FUNCTION__, fn(): ?BookReadDto => $this->repository->findById($id));
    }

    #[\Override]
    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        return $this->tracer->trace('BookRepo::' . __FUNCTION__, fn(): ?BookReadDto => $this->repository->findByIdWithAuthors($id));
    }

    #[\Override]
    public function search(string $term, int $page, int $pageSize): PagedResultInterface
    {
        return $this->tracer->trace(
            'BookRepo::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->repository->search($term, $page, $pageSize)
        );
    }

    #[\Override]
    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        return $this->tracer->trace(
            'BookRepo::' . __FUNCTION__,
            fn(): bool => $this->repository->existsByIsbn($isbn, $excludeId)
        );
    }
}
