<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use RuntimeException;

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

    public static function fromPath(string $path, ?string $extension = null): self
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Cannot open file: ' . $path); // @codeCoverageIgnore
        }

        $extension ??= pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($path);

        if ($mimeType === false) {
            $mimeType = 'application/octet-stream'; // @codeCoverageIgnore
        }

        return new self($stream, $extension, $mimeType);
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
