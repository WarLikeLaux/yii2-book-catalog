<?php

declare(strict_types=1);

namespace app\application\reports\queries;

use app\application\ports\CacheInterface;
use app\application\ports\ReportRepositoryInterface;

final readonly class ReportQueryService
{
    private const int CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private CacheInterface $cache
    ) {
    }

    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $cacheKey = sprintf('report:top_authors:%d', $year);

        /** @var array<array<string, mixed>> $topAuthors */
        $topAuthors = $this->cache->getOrSet(
            $cacheKey,
            fn(): array => $this->reportRepository->getTopAuthorsByYear($year, 10),
            self::CACHE_TTL_SECONDS
        );

        return new ReportDto($topAuthors, $year);
    }

    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return new ReportDto([], $year ?? (int)date('Y'));
    }
}
