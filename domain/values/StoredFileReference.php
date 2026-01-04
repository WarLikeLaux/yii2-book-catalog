<?php

declare(strict_types=1);

namespace app\domain\values;

use Stringable;

final readonly class StoredFileReference implements Stringable
{
    public function __construct(
        private string $path
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
