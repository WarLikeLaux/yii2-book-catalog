<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final readonly class BookYear implements \Stringable
{
    public function __construct(
        public int $value
    ) {
        $currentYear = (int)date('Y');

        if ($this->value <= 1000) {
            throw new DomainException('year.error.too_old');
        }

        if ($this->value > $currentYear + 1) {
            throw new DomainException('year.error.future');
        }
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
