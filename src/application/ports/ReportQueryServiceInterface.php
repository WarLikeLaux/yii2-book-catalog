<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;

interface ReportQueryServiceInterface
{
    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto;

    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto;
}
