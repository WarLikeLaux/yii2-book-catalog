<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use Override;
use yii\db\Connection;
use yii\db\Query;

final readonly class ReportQueryService implements ReportQueryServiceInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    #[Override]
    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $topAuthors = $this->getTopAuthorsByYear($year, 10);

        return new ReportDto($topAuthors, $year);
    }

    #[Override]
    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function getTopAuthorsByYear(int $year, int $limit): array
    {
        return (new Query())
            ->select(['a.id', 'a.fio', 'COUNT(DISTINCT b.id) as books_count'])
            ->from('{{%authors}} a')
            ->innerJoin('{{%book_authors}} ba', 'ba.author_id = a.id')
            ->innerJoin('{{%books}} b', 'b.id = ba.book_id')
            ->where(['b.year' => $year, 'b.is_published' => true])
            ->groupBy(['a.id', 'a.fio'])
            ->orderBy(['books_count' => SORT_DESC])
            ->limit($limit)
            ->all($this->db);
    }
}
