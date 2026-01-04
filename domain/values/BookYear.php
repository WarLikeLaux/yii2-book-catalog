<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;
use DateTimeImmutable;

final readonly class BookYear implements \Stringable
{
    public private(set) int $value;

    public function __construct(
        int $year,
        ?DateTimeImmutable $now = null
    ) {
        if ($now instanceof DateTimeImmutable) {
            $currentYear = (int)$now->format('Y');

            if ($year <= 1000) {
                throw new DomainException('year.error.too_old');
            }

            if ($year > $currentYear + 1) {
                throw new DomainException('year.error.future');
            }
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
