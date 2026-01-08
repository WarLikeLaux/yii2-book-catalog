<?php

declare(strict_types=1);

namespace app\infrastructure\queries\decorators;

use app\application\ports\CacheInterface;
use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;

final readonly class ReportQueryServiceCachingDecorator implements ReportQueryServiceInterface
{
    /**
     * Initialize the caching decorator with the inner report service and cache.
     *
     * @param int $cacheTtl Time-to-live for cached report data in seconds; a value less than or equal to 0 disables caching. Default is 3600.
     */
    public function __construct(
        private ReportQueryServiceInterface $inner,
        private CacheInterface $cache,
        private int $cacheTtl = 3600,
    ) {
    }

    /**
     * Return a top-authors report for the specified criteria, using a per-year cache.
     *
     * The cache key is derived from the report year; if `$criteria->year` is null the current year is used.
     *
     * @param ReportCriteria $criteria Criteria that defines the report (may include `year`).
     * @return ReportDto Report containing the top authors and the year used to generate the report.
     */
    #[\Override]
    public function getTopAuthorsReport(ReportCriteria $criteria): ReportDto
    {
        $year = $criteria->year ?? (int)date('Y');
        $cacheKey = sprintf('report:top_authors:%d', $year);

        if ($this->cacheTtl <= 0) {
            return $this->inner->getTopAuthorsReport($criteria);
        }

        /** @var array<array<string, mixed>> $topAuthors */
        $topAuthors = $this->cache->getOrSet(
            $cacheKey,
            fn(): array => $this->inner->getTopAuthorsReport($criteria)->topAuthors,
            $this->cacheTtl,
        );

        return new ReportDto($topAuthors, $year);
    }

    /**
     * Provide an empty top-authors report for the specified year.
     *
     * @param int|null $year Year for the report; if null, the current year is used.
     * @return ReportDto An empty ReportDto for the given year.
     */
    #[\Override]
    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return $this->inner->getEmptyTopAuthorsReport($year);
    }
}