<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class StoredFileReference implements Stringable
{
    public function __construct(
        private string $path,
    ) {
        if (trim($path) === '') {
            throw new ValidationException(DomainErrorCode::StoredFilePathEmpty);
        }
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
