<?php

declare(strict_types=1);

namespace app\infrastructure\queries\decorators;

use app\application\ports\CacheInterface;
use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use Override;

final readonly class ReportQueryServiceCachingDecorator implements ReportQueryServiceInterface
{
    public function __construct(
        private ReportQueryServiceInterface $inner,
        private CacheInterface $cache,
        private int $cacheTtl = 3600,
    ) {
    }

    #[Override]
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

    #[Override]
    public function getEmptyTopAuthorsReport(?int $year = null): ReportDto
    {
        return $this->inner->getEmptyTopAuthorsReport($year);
    }
}
