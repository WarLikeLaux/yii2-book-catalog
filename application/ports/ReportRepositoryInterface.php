<?php

declare(strict_types=1);

namespace app\application\ports;

interface ReportRepositoryInterface
{
    public function getTopAuthorsByYear(int $year, int $limit): array;
}
