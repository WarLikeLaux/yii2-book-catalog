<?php

declare(strict_types=1);

namespace tests\unit\application\authors\queries;

use app\application\authors\queries\AuthorQueryService;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\PagedResultInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorQueryServiceTest extends Unit
{
    private AuthorRepositoryInterface&MockObject $repository;
    private AuthorQueryService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(AuthorRepositoryInterface::class);
        $this->service = new AuthorQueryService($this->repository);
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
            ->with('', 5, 50)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->getIndexProvider(5, 50);
    }
}
