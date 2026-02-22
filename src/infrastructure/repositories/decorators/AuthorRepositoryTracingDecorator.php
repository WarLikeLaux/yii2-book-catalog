<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\TracerInterface;
use app\domain\entities\Author;
use app\domain\repositories\AuthorRepositoryInterface;
use Override;

final readonly class AuthorRepositoryTracingDecorator implements AuthorRepositoryInterface
{
    public function __construct(
        private AuthorRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function save(Author $author): int
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): int => $this->repository->save($author));
    }

    #[Override]
    public function delete(Author $author): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->delete($author));
    }

    #[Override]
    public function get(int $id): Author
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): Author => $this->repository->get($id));
    }

    #[Override]
    public function removeAllBookLinks(int $authorId): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->removeAllBookLinks($authorId));
    }
}
