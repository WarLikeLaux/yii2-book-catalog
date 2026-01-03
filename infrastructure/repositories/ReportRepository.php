<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\ReportRepositoryInterface;
use yii\db\Connection;

final readonly class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private Connection $db
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getTopAuthorsByYear(int $year, int $limit): array
    {
        return $this->db->createCommand('
            SELECT
                a.id,
                a.fio,
                COUNT(DISTINCT b.id) as books_count
            FROM authors a
            INNER JOIN book_authors ba ON ba.author_id = a.id
            INNER JOIN books b ON b.id = ba.book_id
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
