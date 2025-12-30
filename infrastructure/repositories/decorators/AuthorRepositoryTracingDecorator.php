<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\application\ports\TracerInterface;
use app\domain\entities\Author;

final readonly class AuthorRepositoryTracingDecorator implements AuthorRepositoryInterface
{
    public function __construct(
        private AuthorRepositoryInterface $repository,
        private TracerInterface $tracer
    ) {
    }

    #[\Override]
    public function save(Author $author): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->save($author));
    }

    #[\Override]
    public function get(int $id): Author
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): Author => $this->repository->get($id));
    }

    #[\Override]
    public function findById(int $id): ?AuthorReadDto
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): ?AuthorReadDto => $this->repository->findById($id));
    }

    #[\Override]
    public function delete(Author $author): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->delete($author));
    }

    /**
     * @return AuthorReadDto[]
     */
    #[\Override]
    public function findAllOrderedByFio(): array
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): array => $this->repository->findAllOrderedByFio());
    }

    #[\Override]
    public function search(string $search, int $page, int $pageSize): PagedResultInterface
    {
        return $this->tracer->trace(
            'AuthorRepo::' . __FUNCTION__,
            fn(): PagedResultInterface => $this->repository->search($search, $page, $pageSize)
        );
    }

    #[\Override]
    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        return $this->tracer->trace(
            'AuthorRepo::' . __FUNCTION__,
            fn(): bool => $this->repository->existsByFio($fio, $excludeId)
        );
    }
}
