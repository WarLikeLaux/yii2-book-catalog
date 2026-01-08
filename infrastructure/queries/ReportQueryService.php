<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use yii\db\Connection;

final readonly class ReportQueryService implements ReportQueryServiceInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    #[\Override]
    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $topAuthors = $this->getTopAuthorsByYear($year, 10);

        return new ReportDto($topAuthors, $year);
    }

    #[\Override]
    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }

    /**
     * @return array<array<string, mixed>>
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
