<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class YearSpecification implements BookSpecificationInterface
{
    /**
     * Create a YearSpecification for the given year.
     *
     * @param int $year The year to match against books (e.g., publication year).
     */
    public function __construct(
        private int $year,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitYear($this);
    }

    public function getYear(): int
    {
        return $this->year;
    }
}