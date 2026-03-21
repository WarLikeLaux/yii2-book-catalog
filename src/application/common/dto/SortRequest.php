<?php

declare(strict_types=1);

namespace app\application\common\dto;

final readonly class SortRequest
{
    public function __construct(
        public string $field,
        public SortDirection $direction,
    ) {
    }

    public static function fromRequest(?string $sort): ?self
    {
        if ($sort === null || $sort === '') {
            return null;
        }

        if (str_starts_with($sort, '-')) {
            return new self(substr($sort, 1), SortDirection::DESC);
        }

        return new self($sort, SortDirection::ASC);
    }
}
