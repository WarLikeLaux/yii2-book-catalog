<?php

declare(strict_types=1);

namespace app\presentation\reports\dto;

final readonly class ReportViewModel
{
    /**
     * @param array<array<string, mixed>> $topAuthors
     */
    public function __construct(
        public array $topAuthors,
        public int $year,
    ) {
    }
}
