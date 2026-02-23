<?php

declare(strict_types=1);

namespace app\application\reports\queries;

final readonly class ReportCriteria
{
    public function __construct(
        public ?int $year = null,
    ) {
    }
}
