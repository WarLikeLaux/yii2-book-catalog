<?php

declare(strict_types=1);

namespace app\application\reports\queries;

final readonly class ReportDto
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
