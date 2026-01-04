<?php

declare(strict_types=1);

namespace app\application\reports\queries;

use app\application\ports\CacheInterface;
use app\application\ports\ReportRepositoryInterface;

final readonly class ReportQueryService
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private CacheInterface $cache,
        private int $cacheTtl = 3600,
    ) {
    }

    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $cacheKey = sprintf('report:top_authors:%d', $year);

        if ($this->cacheTtl <= 0) {
            return new ReportDto($this->reportRepository->getTopAuthorsByYear($year, 10), $year);
        }

        /** @var array<array<string, mixed>> $topAuthors */
        $topAuthors = $this->cache->getOrSet(
            $cacheKey,
            fn(): array => $this->reportRepository->getTopAuthorsByYear($year, 10),
            $this->cacheTtl,
        );

        return new ReportDto($topAuthors, $year);
    }

    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }
}
