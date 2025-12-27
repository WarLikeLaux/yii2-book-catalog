<?php

declare(strict_types=1);

namespace tests\unit\application\books\queries;

use app\application\books\queries\BookQueryService;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\PagedResultInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BookQueryServiceTest extends Unit
{
    private BookRepositoryInterface&MockObject $repository;
    private BookQueryService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(BookRepositoryInterface::class);
        $this->service = new BookQueryService($this->repository);
    }

    public function testGetIndexProviderUsesDefaultPagination(): void
    {
        $this->repository->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->getIndexProvider();
    }

    public function testGetIndexProviderUsesCustomPagination(): void
    {
        $this->repository->expects($this->once())
            ->method('search')
            ->with('', 3, 15)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->getIndexProvider(3, 15);
    }
}
