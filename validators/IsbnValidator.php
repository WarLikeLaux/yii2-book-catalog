<?php

declare(strict_types=1);

namespace app\validators;

use yii\validators\Validator;

/**
 * Validates ISBN-10 and ISBN-13 format with checksum verification.
 * Accepts formats: 978-3-16-148410-0, 9783161484100, 0-306-40615-2, 0306406152
 */
final class IsbnValidator extends Validator
{
    public $message = 'Некорректный ISBN. Используйте ISBN-10 или ISBN-13 формат.';

    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        $isbn = $this->normalizeIsbn($value);

        if (!$this->isValidIsbn($isbn)) {
            $this->addError($model, $attribute, $this->message);
        }
    }

    private function normalizeIsbn(string $isbn): string
    {
        return preg_replace('/[\s\-]/', '', $isbn) ?? '';
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

    /**
     * ISBN-10 checksum: (d1*10 + d2*9 + ... + d10*1) % 11 === 0
     */
    private function validateIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/i', $isbn)) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $isbn[$i];
            $value = ($digit === 'X' || $digit === 'x') ? 10 : (int)$digit;
            $checksum += $value * (10 - $i);
        }

        return $checksum % 11 === 0;
    }

    /**
     * ISBN-13 checksum: (d1*1 + d2*3 + d3*1 + ... + d13*1) % 10 === 0
     */
    private function validateIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        if (!str_starts_with($isbn, '978') && !str_starts_with($isbn, '979')) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < 13; $i++) {
            $weight = ($i % 2 === 0) ? 1 : 3;
            $checksum += (int)$isbn[$i] * $weight;
        }

        return $checksum % 10 === 0;
    }
}
