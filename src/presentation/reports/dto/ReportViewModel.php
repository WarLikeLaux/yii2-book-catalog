<?php

declare(strict_types=1);

namespace app\presentation\reports\dto;

use app\presentation\common\ViewModelInterface;

final readonly class ReportViewModel implements ViewModelInterface
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
