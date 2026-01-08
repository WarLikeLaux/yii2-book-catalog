<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;

interface ReportQueryServiceInterface
{
    /**
 * Retrieve a report of top authors matching the provided criteria.
 *
 * @param ReportCriteria $criteria Criteria used to select and rank authors for the report.
 * @return ReportDto A DTO containing the top authors and their associated metrics.
 */
public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto;

    /**
 * Produce an empty top-authors report for the specified year.
 *
 * @param int|null $year The year to associate with the empty report, or null if no year is specified.
 * @return ReportDto A ReportDto representing an empty top-authors report for the given year.
 */
public function getEmptyTopAuthorsReport(?int $year = null): ReportDto;
}