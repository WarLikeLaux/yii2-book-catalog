<?php

declare(strict_types=1);

namespace app\domain\valueObjects;

use app\domain\exceptions\InvalidIsbnException;

final readonly class Isbn
{
    private function __construct(
        private string $value
    ) {}

    public static function fromString(string $isbn): self
    {
        $normalized = self::normalize($isbn);

        if (!self::isValid($normalized)) {
            throw new InvalidIsbnException("Invalid ISBN: {$isbn}");
        }

        return new self($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function normalize(string $isbn): string
    {
        return preg_replace('/[\s\-]/', '', $isbn) ?? '';
    }

    private static function isValid(string $isbn): bool
    {
        $length = strlen($isbn);

        return match ($length) {
            10 => self::validateIsbn10($isbn),
            13 => self::validateIsbn13($isbn),
            default => false,
        };
    }

    private static function validateIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/i', $isbn)) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $isbn[$i];
            $value = $digit === 'X' || $digit === 'x' ? 10 : (int)$digit;
            $checksum += $value * (10 - $i);
        }

        return $checksum % 11 === 0;
    }

    private static function validateIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        if (!str_starts_with($isbn, '978') && !str_starts_with($isbn, '979')) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < 13; $i++) {
            $weight = $i % 2 === 0 ? 1 : 3;
            $checksum += (int)$isbn[$i] * $weight;
        }

        return $checksum % 10 === 0;
    }
}
