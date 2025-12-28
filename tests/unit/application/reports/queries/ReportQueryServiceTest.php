<?php

declare(strict_types=1);

namespace tests\unit\application\reports\queries;

use app\application\ports\CacheInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportQueryService;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ReportQueryServiceTest extends Unit
{
    private ReportRepositoryInterface&MockObject $repository;
    private CacheInterface&MockObject $cache;
    private ReportQueryService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(ReportRepositoryInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->service = new ReportQueryService($this->repository, $this->cache);
    }

    public function testGetTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $currentYear = (int)date('Y');

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->with(
                "report:top_authors:{$currentYear}",
                $this->isType('callable'),
                3600
            )
            ->willReturn([]);

        $criteria = new ReportCriteria(null);

        $dto = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame($currentYear, $dto->year);
    }

    public function testGetTopAuthorsReportUsesProvidedYear(): void
    {
        $year = 2020;

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->with(
                "report:top_authors:{$year}",
                $this->isType('callable'),
                3600
            )
            ->willReturn([]);

        $criteria = new ReportCriteria($year);

        $dto = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame($year, $dto->year);
    }

    public function testGetTopAuthorsReportReturnsCachedData(): void
    {
        $year = 2023;
        $cachedData = [
            ['id' => 1, 'fio' => 'Author 1', 'books_count' => 5],
            ['id' => 2, 'fio' => 'Author 2', 'books_count' => 3],
        ];

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->willReturn($cachedData);

        $this->repository->expects($this->never())
            ->method('getTopAuthorsByYear');

        $criteria = new ReportCriteria($year);

        $dto = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame($cachedData, $dto->topAuthors);
        $this->assertSame($year, $dto->year);
    }

    public function testCacheCallbackInvokesRepository(): void
    {
        $year = 2022;
        $expectedData = [['id' => 1, 'fio' => 'Test', 'books_count' => 10]];

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->willReturnCallback(function ($key, $callback) use ($expectedData) {
                $this->repository->expects($this->once())
                    ->method('getTopAuthorsByYear')
                    ->with(2022, 10)
                    ->willReturn($expectedData);
                return $callback();
            });

        $criteria = new ReportCriteria($year);
        $dto = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame($expectedData, $dto->topAuthors);
    }

    public function testGetEmptyTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $dto = $this->service->getEmptyTopAuthorsReport();
        $this->assertSame((int)date('Y'), $dto->year);
        $this->assertSame([], $dto->topAuthors);
    }

    public function testGetEmptyTopAuthorsReportUsesProvidedYear(): void
    {
        $dto = $this->service->getEmptyTopAuthorsReport(2000);
        $this->assertSame(2000, $dto->year);
    }
}

