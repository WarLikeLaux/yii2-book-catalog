<?php

declare(strict_types=1);

namespace app\tests\unit\application\authors\queries;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchCriteria;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorQueryServiceTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryPort;

    private AuthorQueryService $service;

    protected function _before(): void
    {
        $this->queryPort = $this->createMock(AuthorQueryServiceInterface::class);
        $this->service = new AuthorQueryService($this->queryPort);
    }

    public function testGetIndexProvider(): void
    {
        $this->queryPort->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->getIndexProvider();
    }

    public function testGetAuthorsMap(): void
    {
        $this->queryPort->expects($this->once())
            ->method('findAllOrderedByFio')
            ->willReturn([
                new AuthorReadDto(1, 'Author 1'),
                new AuthorReadDto(2, 'Author 2'),
            ]);

        $result = $this->service->getAuthorsMap();
        $this->assertSame([1 => 'Author 1', 2 => 'Author 2'], $result);
    }

    public function testGetById(): void
    {
        $dto = new AuthorReadDto(1, 'Test');
        $this->queryPort->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $result = $this->service->getById(1);
        $this->assertSame($dto, $result);
    }

    public function testGetByIdThrowsExceptionWhenNotFound(): void
    {
        $this->queryPort->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('author.error.not_found');

        $this->service->getById(999);
    }

    public function testSearch(): void
    {
        $criteria = new AuthorSearchCriteria(search: 'term', page: 1, pageSize: 10);
        $pagedResult = $this->createMock(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([]);
        $pagedResult->method('getTotalCount')->willReturn(0);

        $this->queryPort->expects($this->once())
            ->method('search')
            ->with('term', 1, 10)
            ->willReturn($pagedResult);

        $response = $this->service->search($criteria);
        $this->assertSame(0, $response->total);
    }

    public function testFindMissingIds(): void
    {
        $ids = [1, 2, 3];
        $missing = [2];

        $this->queryPort->expects($this->once())
            ->method('findMissingIds')
            ->with($ids)
            ->willReturn($missing);

        $result = $this->service->findMissingIds($ids);

        $this->assertSame($missing, $result);
    }
}
