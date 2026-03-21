<?php

declare(strict_types=1);

namespace app\application\common\values;

use app\application\common\exceptions\StorageErrorCode;
use app\application\common\exceptions\StorageException;

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
            throw new StorageException(StorageErrorCode::InvalidStream);
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
