<?php

declare(strict_types=1);

namespace app\application\reports\queries;

use app\models\forms\ReportFilterForm;
use yii\db\Connection;

final class ReportQueryService
{
    public function __construct(
        private readonly Connection $db
    ) {
    }

    public function getTopAuthorsReport(ReportFilterForm $form): ReportDto
    {
        $year = $form->year ? (int)$form->year : (int)date('Y');
        $topAuthors = $this->getTopAuthorsByYear($year);

        return new ReportDto($topAuthors, $year);
    }

    public function getEmptyTopAuthorsReport(int|string|null $year = null): ReportDto
    {
        return new ReportDto([], $year ? (int)$year : (int)date('Y'));
    }

    private function getTopAuthorsByYear(int $year, int $limit = 10): array
    {
        return $this->db->createCommand('
            SELECT
                a.id,
                a.fio,
                COUNT(DISTINCT b.id) as books_count
            FROM authors a
            INNER JOIN book_authors ba ON ba.author_id = a.id
            INNER JOIN books b ON b.id = ba.book_id
            WHERE b.year = :year
            GROUP BY a.id, a.fio
            ORDER BY books_count DESC
            LIMIT :limit
        ')
            ->bindValue(':year', $year)
            ->bindValue(':limit', $limit)
            ->queryAll();
    }
}
