<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class Isbn implements Stringable
{
    private const int ISBN10_LENGTH = 10;
    private const int ISBN13_LENGTH = 13;
    private const int ISBN10_CHECKSUM_MODULO = 11;
    private const int ISBN13_CHECKSUM_MODULO = 10;
    private const int ISBN10_X_VALUE = 10;
    private const int ISBN13_WEIGHT_EVEN = 1;
    private const int ISBN13_WEIGHT_ODD = 3;
    private const array ISBN13_PREFIXES = ['978', '979'];

    public private(set) string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalizeIsbn($value);

        if (!self::isValid($normalized)) {
            throw new ValidationException(DomainErrorCode::IsbnInvalidFormat);
        }

        $this->value = $normalized;
    }

    public static function isValid(string $value): bool
    {
        $isbn = self::normalizeIsbn($value);
        $length = strlen($isbn);

        return match ($length) {
            self::ISBN10_LENGTH => self::validateIsbn10($isbn),
            self::ISBN13_LENGTH => self::validateIsbn13($isbn),
            default => false,
        };
    }

    private static function normalizeIsbn(string $isbn): string
    {
        return (string)preg_replace('/[\s\-]/', '', $isbn);
    }

    private static function validateIsbn10(string $isbn): bool
    {
        if (!ctype_digit(substr($isbn, 0, self::ISBN10_LENGTH - 1))) {
            return false;
        }

        $last = $isbn[self::ISBN10_LENGTH - 1];

        if (!ctype_digit($last) && $last !== 'X' && $last !== 'x') {
            return false;
        }

        $toDigitValue = static fn(string $char): int => $char === 'X' || $char === 'x'
        ? self::ISBN10_X_VALUE
        : (int)$char;

        $checksum = 0;

        foreach (str_split($isbn) as $index => $digit) {
            $checksum += $toDigitValue($digit) * (self::ISBN10_LENGTH - $index);
        }

        return $checksum % self::ISBN10_CHECKSUM_MODULO === 0;
    }

    private static function validateIsbn13(string $isbn): bool
    {
        if (!ctype_digit($isbn)) {
            return false;
        }

        if (!self::hasValidIsbn13Prefix($isbn)) {
            return false;
        }

        $checksum = 0;

        for ($i = 0; $i < self::ISBN13_LENGTH; $i++) {
            $weight = $i % 2 === 0 ? self::ISBN13_WEIGHT_EVEN : self::ISBN13_WEIGHT_ODD;
            $checksum += (int)$isbn[$i] * $weight;
        }

        return $checksum % self::ISBN13_CHECKSUM_MODULO === 0;
    }

    private static function hasValidIsbn13Prefix(string $isbn): bool
    {
        return array_any(self::ISBN13_PREFIXES, static fn(string $prefix): bool => str_starts_with($isbn, $prefix));
    }

    public function getFormatted(): string
    {
        if (strlen($this->value) === self::ISBN13_LENGTH) {
            return substr($this->value, 0, 3) . '-' . $this->value[3] . '-' . substr($this->value, 4, 2) . '-' . substr($this->value, 6, 6) . '-' . $this->value[self::ISBN13_LENGTH - 1];
        }

        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
