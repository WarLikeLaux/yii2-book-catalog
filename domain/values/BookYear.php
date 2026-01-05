<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final readonly class BookYear implements \Stringable
{
    public private(set) int $value;

    public function __construct(int $year, ?int $currentYear = null)
    {
        if ($year <= 1000) {
            throw new DomainException('year.error.too_old');
        }

        if ($currentYear !== null && $year > $currentYear + 1) {
            throw new DomainException('year.error.future');
        }

        $this->value = $year;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
