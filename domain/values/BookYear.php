<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final class BookYear
{
    public readonly int $value;

    public function __construct(int $value)
    {
        $currentYear = (int)date('Y');

        if ($value < 1000) {
            throw new DomainException('Invalid year: must be greater than 1000.');
        }

        if ($value > $currentYear + 1) {
            throw new DomainException('Invalid year: cannot be in the future.');
        }

        $this->value = $value;
    }
}
