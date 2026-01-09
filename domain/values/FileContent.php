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

    public static function fromPath(string $path, ?string $extension = null): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ValidationException(DomainErrorCode::FileNotFound);
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new ValidationException(DomainErrorCode::FileOpenFailed); // @codeCoverageIgnore
        }

        $extension ??= pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = 'application/octet-stream';

        if (function_exists('mime_content_type')) {
            $mimeValue = mime_content_type($path);
            $mimeType = $mimeValue !== false ? $mimeValue : $mimeType;
        } elseif (function_exists('finfo_open')) { // @codeCoverageIgnoreStart
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mimeValue = finfo_file($finfo, $path);
                $mimeType = $mimeValue !== false ? $mimeValue : $mimeType;
                finfo_close($finfo);
            }
        } // @codeCoverageIgnoreEnd

        return new self($stream, $extension, $mimeType);
    }

    /**
     * @return resource
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    public function __destruct()
    {
        if (!is_resource($this->stream)) {
            return; // @codeCoverageIgnore
        }

        fclose($this->stream);
    }

    public function computeKey(): FileKey
    {
        return FileKey::fromStream($this->stream);
    }
}
