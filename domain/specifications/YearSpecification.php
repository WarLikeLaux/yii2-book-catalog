<?php

declare(strict_types=1);

namespace app\domain\specifications;

/** Критерий поиска, не VO - принимает любой год, валидация не нужна */
final readonly class YearSpecification implements BookSpecificationInterface
{
    public function __construct(
        private int $year,
    ) {
    }

    public function toSearchCriteria(): array
    {
        return [
            'type' => 'year',
            'value' => $this->year,
        ];
    }
}
