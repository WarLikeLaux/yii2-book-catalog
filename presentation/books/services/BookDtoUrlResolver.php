<?php

declare(strict_types=1);

namespace app\presentation\books\services;

use app\application\books\queries\BookReadDto;
use app\presentation\services\FileUrlResolver;

final readonly class BookDtoUrlResolver
{
    public function __construct(
        private FileUrlResolver $resolver,
    ) {
    }

    public function resolveUrl(BookReadDto $dto): BookReadDto
    {
        return $dto->withCoverUrl(
            $this->resolver->resolveCoverUrl($dto->coverUrl, $dto->id),
        );
    }

    public function resolveRealUrl(BookReadDto $dto): BookReadDto
    {
        return $dto->withCoverUrl(
            $this->resolver->resolve($dto->coverUrl),
        );
    }
}
