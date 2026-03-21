<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\TracerInterface;
use app\domain\entities\Book;
use app\domain\repositories\BookRepositoryInterface;
use Override;

final readonly class BookRepositoryTracingDecorator implements BookRepositoryInterface
{
    public function __construct(
        private BookRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function save(Book $book, ?int $expectedVersion = null): int
    {
        return $this->tracer->trace('BookRepo::' . __FUNCTION__, fn(): int => $this->repository->save($book, $expectedVersion));
    }

    #[Override]
    public function get(int $id): Book
    {
        return $this->tracer->trace('BookRepo::' . __FUNCTION__, fn(): Book => $this->repository->get($id));
    }

    #[Override]
    public function delete(Book $book): void
    {
        $this->tracer->trace('BookRepo::' . __FUNCTION__, fn() => $this->repository->delete($book));
    }
}
