<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries\decorators;

use app\application\ports\CacheInterface;
use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use app\infrastructure\queries\decorators\ReportQueryServiceCachingDecorator;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ReportQueryServiceCachingDecoratorTest extends Unit
{
    private ReportQueryServiceInterface&MockObject $inner;
    private CacheInterface&MockObject $cache;
    private ReportQueryServiceCachingDecorator $decorator;

    protected function _before(): void
    {
        $this->inner = $this->createMock(ReportQueryServiceInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->decorator = new ReportQueryServiceCachingDecorator($this->inner, $this->cache);
    }

    public function testGetTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $currentYear = (int)date('Y');

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->with(
                "report:top_authors:{$currentYear}",
                $this->isType('callable'),
                3600,
            )
            ->willReturn([]);

        $criteria = new ReportCriteria(null);

        $dto = $this->decorator->getTopAuthorsReport($criteria);

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
                3600,
            )
            ->willReturn([]);

        $criteria = new ReportCriteria($year);

        $dto = $this->decorator->getTopAuthorsReport($criteria);

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

        $this->inner->expects($this->never())
            ->method('getTopAuthorsReport');

        $criteria = new ReportCriteria($year);

        $dto = $this->decorator->getTopAuthorsReport($criteria);

        $this->assertSame($cachedData, $dto->topAuthors);
        $this->assertSame($year, $dto->year);
    }

    public function testCacheCallbackInvokesInner(): void
    {
        $year = 2022;
        $expectedData = [['id' => 1, 'fio' => 'Test', 'books_count' => 10]];
        $expectedDto = new ReportDto($expectedData, $year);

        $this->inner->expects($this->once())
            ->method('getTopAuthorsReport')
            ->willReturn($expectedDto);

        $this->cache->expects($this->once())
            ->method('getOrSet')
            ->willReturnCallback(static fn ($key, $callback) => $callback()); // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter -- Параметр $key обязателен для сигнатуры, но не используется в моке

        $criteria = new ReportCriteria($year);
        $dto = $this->decorator->getTopAuthorsReport($criteria);

        $this->assertSame($expectedData, $dto->topAuthors);
    }

    public function testGetEmptyTopAuthorsReportDelegatesToInner(): void
    {
        $expectedDto = new ReportDto([], 2000);

        $this->inner->expects($this->once())
            ->method('getEmptyTopAuthorsReport')
            ->with(2000)
            ->willReturn($expectedDto);

        $dto = $this->decorator->getEmptyTopAuthorsReport(2000);

        $this->assertSame(2000, $dto->year);
        $this->assertSame([], $dto->topAuthors);
    }

    public function testGetTopAuthorsReportBypassesCacheWhenTtlIsZero(): void
    {
        $year = 2023;
        $expectedData = [['id' => 1, 'fio' => 'Direct', 'books_count' => 5]];
        $expectedDto = new ReportDto($expectedData, $year);

        $this->inner->expects($this->once())
            ->method('getTopAuthorsReport')
            ->willReturn($expectedDto);

        $this->cache->expects($this->never())
            ->method('getOrSet');

        $decorator = new ReportQueryServiceCachingDecorator($this->inner, $this->cache, 0);

        $criteria = new ReportCriteria($year);
        $dto = $decorator->getTopAuthorsReport($criteria);

        $this->assertSame($expectedData, $dto->topAuthors);
        $this->assertSame($year, $dto->year);
    }
}
