<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\TracerInterface;
use app\domain\entities\Author;

final readonly class AuthorRepositoryTracingDecorator implements AuthorRepositoryInterface
{
    public function __construct(
        private AuthorRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    #[\Override]
    public function save(Author $author): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->save($author));
    }

    #[\Override]
    public function delete(Author $author): void
    {
        $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn() => $this->repository->delete($author));
    }

    #[\Override]
    public function get(int $id): Author
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): Author => $this->repository->get($id));
    }
}
