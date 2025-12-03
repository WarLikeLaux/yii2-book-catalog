<?php

declare(strict_types=1);

namespace app\services;

use yii\db\Connection;

final class ReportService
{
    public function __construct(
        private readonly Connection $db
    ) {
    }

    /**
     * @return array<int, array{id: int, fio: string, books_count: int}>
     */
    public function getTopAuthorsByYear(int $year, int $limit = 10): array
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
