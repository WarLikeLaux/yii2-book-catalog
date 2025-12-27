<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final readonly class Isbn implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = $this->normalizeIsbn($value);

        if (!$this->isValidIsbn($normalized)) {
            throw new DomainException('Invalid ISBN format.');
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

        $checksum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $isbn[$i];
            $digitValue = $digit === 'X' || $digit === 'x' ? 10 : (int)$digit;
            $checksum += $digitValue * (10 - $i);
        }

        return $checksum % 11 === 0;
    }

    private function validateIsbn13(string $isbn): bool
    {
        if (!ctype_digit($isbn)) {
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
