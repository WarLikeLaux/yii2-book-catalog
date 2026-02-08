<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Stringable;

final readonly class Isbn implements Stringable
{
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
            10 => self::validateIsbn10($isbn),
            13 => self::validateIsbn13($isbn),
            default => false,
        };
    }

    private static function normalizeIsbn(string $isbn): string
    {
        return (string)preg_replace('/[\s\-]/', '', $isbn);
    }

    private static function validateIsbn10(string $isbn): bool
    {
        if (!ctype_digit(substr($isbn, 0, 9))) {
            return false;
        }

        $last = $isbn[9];

        if (!ctype_digit($last) && $last !== 'X' && $last !== 'x') {
            return false;
        }

        $weightedDigits = [];

        foreach (str_split($isbn) as $index => $digit) {
            $digitValue = $digit === 'X' || $digit === 'x' ? 10 : ord($digit) - 48;
            $weightedDigits[] = $digitValue * (10 - $index);
        }

        return array_sum($weightedDigits) % 11 === 0;
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

        for ($i = 0; $i < 13; $i++) {
            $weight = $i % 2 === 0 ? 1 : 3;
            $checksum += (int)$isbn[$i] * $weight;
        }

        return $checksum % 10 === 0;
    }

    private static function hasValidIsbn13Prefix(string $isbn): bool
    {
        foreach (self::ISBN13_PREFIXES as $prefix) {
            if (str_starts_with($isbn, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function getFormatted(): string
    {
        if (strlen($this->value) === 13) {
            return substr($this->value, 0, 3) . '-' . $this->value[3] . '-' . substr($this->value, 4, 2) . '-' . substr($this->value, 6, 6) . '-' . $this->value[12];
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
