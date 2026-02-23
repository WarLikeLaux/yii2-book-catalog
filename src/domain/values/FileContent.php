<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;

final readonly class FileContent
{
    /**
     * @param resource $stream
     */
    public function __construct(
        private mixed $stream,
        public string $extension,
        public string $mimeType,
    ) {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new ValidationException(DomainErrorCode::FileContentInvalidStream);
        }
    }

    /**
     * @return resource
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    public function computeKey(): FileKey
    {
        return FileKey::fromStream($this->stream);
    }
}
