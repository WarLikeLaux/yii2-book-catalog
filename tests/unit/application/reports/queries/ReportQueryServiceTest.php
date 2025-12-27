<?php

declare(strict_types=1);

namespace tests\unit\application\reports\queries;

use app\application\ports\ReportRepositoryInterface;
use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportQueryService;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class ReportQueryServiceTest extends Unit
{
    private ReportRepositoryInterface&MockObject $repository;
    private ReportQueryService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(ReportRepositoryInterface::class);
        $this->service = new ReportQueryService($this->repository);
    }

    public function testGetTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $currentYear = (int)date('Y');
        
        $this->repository->expects($this->once())
            ->method('getTopAuthorsByYear')
            ->with($currentYear, 10)
            ->willReturn([]);
            
        $criteria = new ReportCriteria(null);

        $dto = $this->service->getTopAuthorsReport($criteria);
        
        $this->assertSame($currentYear, $dto->year);
    }
    
    public function testGetTopAuthorsReportUsesProvidedYear(): void
    {
        $year = 2020;
        
        $this->repository->expects($this->once())
            ->method('getTopAuthorsByYear')
            ->with($year, 10)
            ->willReturn([]);
            
        $criteria = new ReportCriteria($year);
        
        $dto = $this->service->getTopAuthorsReport($criteria);
        
        $this->assertSame($year, $dto->year);
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
