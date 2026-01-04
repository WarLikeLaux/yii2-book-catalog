<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\BookRepositoryInterface;
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
    public function getByIdAndVersion(int $id, int $expectedVersion): Book
    {
        return $this->tracer->trace(
            'BookRepo::' . __FUNCTION__,
            fn(): Book => $this->repository->getByIdAndVersion($id, $expectedVersion)
        );
    }

    #[\Override]
    public function delete(Book $book): void
    {
        $this->tracer->trace('BookRepo::' . __FUNCTION__, fn() => $this->repository->delete($book));
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
