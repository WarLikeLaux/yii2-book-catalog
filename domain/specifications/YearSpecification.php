<?php

declare(strict_types=1);

namespace app\domain\specifications;

use app\domain\values\BookYear;

final readonly class YearSpecification implements BookSpecificationInterface
{
    public function __construct(
        private BookYear $year
    ) {
    }

    public function toSearchCriteria(): array
    {
        return [
            'type' => 'year',
            'value' => $this->year->value,
        ];
    }
}
