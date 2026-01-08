<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class FileKey implements Stringable
{
    private const int HASH_LENGTH = 64;

    public private(set) string $value;

    /**
     * Create a FileKey from a hexadecimal hash string.
     *
     * Normalizes the input to lowercase and validates it is a 64-character hexadecimal hash;
     * throws a ValidationException with DomainErrorCode::FileKeyInvalidFormat on failure.
     *
     * @param string $hash The input hash string (case-insensitive).
     * @throws ValidationException If the normalized hash is not a 64-character hexadecimal string.
     */
    public function __construct(string $hash)
    {
        $normalized = strtolower($hash);

        if (!$this->isValidHash($normalized)) {
            throw new ValidationException(DomainErrorCode::FileKeyInvalidFormat);
        }

        $this->value = $normalized;
    }

    /**
     * Create a FileKey from the contents of a stream by computing the stream's SHA-256 hash.
     *
     * The stream is rewound to its original position after hashing.
     *
     * @param resource $stream The stream whose contents will be hashed; must be readable and seekable.
     * @return self A FileKey representing the SHA-256 hex hash of the stream contents.
     */
    public static function fromStream(mixed $stream): self
    {
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        rewind($stream);

        return new self(hash_final($context));
    }

    /**
     * Builds a storage path from the file key using two-level directory sharding and an optional extension.
     *
     * The path is formatted as "<first2>/<second2>/<hash>[.extension]".
     *
     * @param string $extension Optional file extension without a leading dot.
     * @return string The constructed path string.
     */
    public function getExtendedPath(string $extension = ''): string
    {
        $suffix = $extension !== '' ? '.' . $extension : '';

        return substr($this->value, 0, 2)
            . '/'
            . substr($this->value, 2, 2)
            . '/'
            . $this->value
            . $suffix;
    }

    /**
     * Determine whether this FileKey represents the same hash as another.
     *
     * @param self $other The FileKey to compare against.
     * @return bool `true` if both instances contain the identical hash value, `false` otherwise.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Checks whether a string is a valid SHA-256 hex hash.
     *
     * @param string $hash The string to validate.
     * @return bool `true` if the string contains exactly `self::HASH_LENGTH` hexadecimal characters, `false` otherwise.
     */
    private function isValidHash(string $hash): bool
    {
        if (strlen($hash) !== self::HASH_LENGTH) {
            return false;
        }

        return ctype_xdigit($hash);
    }

    /**
     * Return the stored hash value.
     *
     * @return string The normalized lowercase 64-character hexadecimal hash.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}