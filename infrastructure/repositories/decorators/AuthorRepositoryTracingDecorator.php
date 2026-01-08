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

    /**
     * Retrieve an Author by its identifier while recording the operation in the tracer.
     *
     * @param int $id The author's identifier.
     * @return Author The Author matching the given identifier.
     */
    #[\Override]
    public function get(int $id): Author
    {
        return $this->tracer->trace('AuthorRepo::' . __FUNCTION__, fn(): Author => $this->repository->get($id));
    }
}