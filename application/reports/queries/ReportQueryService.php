<?php

declare(strict_types=1);

namespace app\application\reports\queries;

use app\application\ports\ReportRepositoryInterface;

final class ReportQueryService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository
    ) {
    }

    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $topAuthors = $this->reportRepository->getTopAuthorsByYear($year, 10);

        return new ReportDto($topAuthors, $year);
    }

    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }
}
