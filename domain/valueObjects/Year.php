<?php

declare(strict_types=1);

namespace app\domain\valueObjects;

use app\domain\exceptions\InvalidYearException;

final readonly class Year
{
    private function __construct(
        private int $value
    ) {}

    public static function fromInt(int $year): self
    {
        if (!self::isValid($year)) {
            throw new InvalidYearException("Invalid year: {$year}. Must be between 1000 and " . (date('Y') + 1));
        }

        return new self($year);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function isValid(int $year): bool
    {
        $currentYear = (int)date('Y');
        return $year >= 1000 && $year <= $currentYear + 1;
    }
}
