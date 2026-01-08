<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use yii\db\Connection;

final readonly class ReportQueryService implements ReportQueryServiceInterface
{
    /**
     * Initialize the query service with the provided database connection.
     */
    public function __construct(
        private Connection $db,
    ) {
    }

    /**
     * Produce a report of the top authors for the specified year.
     *
     * @param ReportCriteria $criteria Criteria containing an optional `year`; if `year` is null the current year is used.
     * @return ReportDto Report containing the top 10 authors for the resolved year and the year value used.
     */
    #[\Override]
    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $topAuthors = $this->getTopAuthorsByYear($year, 10);

        return new ReportDto($topAuthors, $year);
    }

    /**
     * Create an empty top-authors report for the specified year or the current year.
     *
     * @param int|null $year The year for which to create the report; when null the current year is used.
     * @return ReportDto A ReportDto containing an empty authors list and the resolved year.
     */
    #[\Override]
    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }

    /**
     * Retrieve top authors for a given year, limited to a maximum number of authors.
     *
     * @param int $year Year used to filter books.
     * @param int $limit Maximum number of authors to return.
     * @return array<array<string, mixed>> An array of associative arrays with keys `id` (author id), `fio` (author full name), and `books_count` (count of distinct published books for the specified year).
     */
    private function getTopAuthorsByYear(int $year, int $limit): array
    {
        return $this->db->createCommand('
            SELECT
                a.id,
                a.fio,
                COUNT(DISTINCT b.id) as books_count
            FROM {{%authors}} a
            INNER JOIN {{%book_authors}} ba ON ba.author_id = a.id
            INNER JOIN {{%books}} b ON b.id = ba.book_id
            WHERE b.year = :year AND b.is_published = TRUE
            GROUP BY a.id, a.fio
            ORDER BY books_count DESC
            LIMIT :limit
        ')
            ->bindValue(':year', $year)
            ->bindValue(':limit', $limit)
            ->queryAll();
    }
}