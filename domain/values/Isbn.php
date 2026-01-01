<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final readonly class Isbn implements \Stringable
{
    private const array ISBN13_PREFIXES = ['978', '979'];

    public string $value;

    public function __construct(string $value)
    {
        $normalized = $this->normalizeIsbn($value);

        if (!$this->isValidIsbn($normalized)) {
            throw new DomainException('isbn.error.invalid_format');
        }

        $this->value = $normalized;
    }

    public function getFormatted(): string
    {
        if (strlen($this->value) === 13) {
            return substr($this->value, 0, 3) . '-' . $this->value[3] . '-' . substr($this->value, 4, 2) . '-' . substr($this->value, 6, 6) . '-' . $this->value[12];
        }

        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function normalizeIsbn(string $isbn): string
    {
        return (string)preg_replace('/[\s\-]/', '', $isbn);
    }

    private function isValidIsbn(string $isbn): bool
    {
        $length = strlen($isbn);

        return match ($length) {
            10 => $this->validateIsbn10($isbn),
            13 => $this->validateIsbn13($isbn),
            default => false,
        };
    }

    private function validateIsbn10(string $isbn): bool
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

    private function validateIsbn13(string $isbn): bool
    {
        if (!ctype_digit($isbn)) {
            return false;
        }

        if (!$this->hasValidIsbn13Prefix($isbn)) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < 13; $i++) {
            $weight = $i % 2 === 0 ? 1 : 3;
            $checksum += (int)$isbn[$i] * $weight;
        }

        return $checksum % 10 === 0;
    }

    private function hasValidIsbn13Prefix(string $isbn): bool
    {
        foreach (self::ISBN13_PREFIXES as $prefix) {
            if (str_starts_with($isbn, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
