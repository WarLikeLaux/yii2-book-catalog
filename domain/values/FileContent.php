<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use RuntimeException;

final readonly class FileContent
{
    /**
     * Create a FileContent from an open stream, file extension, and MIME type.
     *
     * @param resource $stream An open stream resource of type "stream".
     * @param string $extension File extension (without leading dot).
     * @param string $mimeType MIME type for the stream's content.
     *
     * @throws ValidationException If `$stream` is not a valid stream resource.
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
     * Create a FileContent instance from a filesystem path.
     *
     * The returned instance contains an opened binary stream for the file; the caller is responsible for closing the stream with fclose().
     *
     * @param string $path Path to the file to open.
     * @param string|null $extension Optional file extension to use instead of deriving it from the path.
     * @return self Instance containing an opened stream, the resolved extension, and the determined MIME type.
     * @throws RuntimeException If the file cannot be opened.
     */
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
     * Access the underlying stream resource for this file content.
     *
     * @return resource The opened stream resource for the file; the caller is responsible for closing it with `fclose()`.
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Produces a FileKey that uniquely identifies the file content held by this instance.
     *
     * @return FileKey The key derived from the instance's underlying stream.
     */
    public function computeKey(): FileKey
    {
        return FileKey::fromStream($this->stream);
    }
}