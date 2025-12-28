<?php

declare(strict_types=1);

namespace app\domain\values;

use app\domain\exceptions\DomainException;

final readonly class BookYear
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
}
