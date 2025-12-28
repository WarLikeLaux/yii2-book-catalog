<?php

declare(strict_types=1);

namespace app\application\ports;

interface ReportRepositoryInterface
{
    /**
     * @return array<array<string, mixed>>
     */
    public function getTopAuthorsByYear(int $year, int $limit): array;
}
