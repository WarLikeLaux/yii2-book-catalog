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

    public function __construct(string $hash)
    {
        $normalized = strtolower($hash);

        if (!$this->isValidHash($normalized)) {
            throw new ValidationException(DomainErrorCode::FileKeyInvalidFormat);
        }

        $this->value = $normalized;
    }

    /**
     * @param resource $stream
     */
    public static function fromStream(mixed $stream): self
    {
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        rewind($stream);

        return new self(hash_final($context));
    }

    public static function assertValidExtension(string $extension): string
    {
        $normalizedExtension = strtolower(ltrim($extension, '.'));

        if ($normalizedExtension !== '' && preg_match('/^[a-z0-9_-]+$/', $normalizedExtension) !== 1) {
            throw new ValidationException(DomainErrorCode::FileKeyInvalidFormat);
        }

        return $normalizedExtension;
    }

    public function getExtendedPath(string $extension = ''): string
    {
        $normalizedExtension = self::assertValidExtension($extension);

        $suffix = $normalizedExtension !== '' ? '.' . $normalizedExtension : '';

        return substr($this->value, 0, 2)
        . '/'
        . substr($this->value, 2, 2)
        . '/'
        . $this->value
        . $suffix;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValidHash(string $hash): bool
    {
        if (strlen($hash) !== self::HASH_LENGTH) {
            return false;
        }

        return ctype_xdigit($hash);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
