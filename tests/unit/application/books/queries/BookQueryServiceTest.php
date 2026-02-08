<?php

declare(strict_types=1);

namespace app\tests\unit\application\books\queries;

use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\application\books\queries\BookSearchCriteria;
use app\application\ports\BookFinderInterface;
use app\application\ports\BookSearcherInterface;
use app\application\ports\PagedResultInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BookQueryServiceTest extends Unit
{
    private BookFinderInterface&MockObject $finder;
    private BookSearcherInterface&MockObject $searcher;
    private BookQueryService $service;

    protected function _before(): void
    {
        $this->finder = $this->createMock(BookFinderInterface::class);
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $this->service = new BookQueryService($this->finder, $this->searcher);
    }

    public function testGetIndexProvider(): void
    {
        $this->searcher->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->getIndexProvider();
    }

    public function testGetById(): void
    {
        $dto = new BookReadDto(1, 'Title', 2025, null, '9783161484100', [], [], null);
        $this->finder->expects($this->once())
            ->method('findByIdWithAuthors')
            ->with(1)
            ->willReturn($dto);

        $result = $this->service->getById(1);
        $this->assertSame($dto, $result);
    }

    public function testGetByIdThrowsExceptionWhenNotFound(): void
    {
        $this->finder->expects($this->once())
            ->method('findByIdWithAuthors')
            ->with(999)
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('book.error.not_found');

        $this->service->getById(999);
    }

    public function testSearch(): void
    {
        $criteria = new BookSearchCriteria(globalSearch: 'term', page: 1, limit: 10);
        $this->searcher->expects($this->once())
            ->method('search')
            ->with('term', 1, 10)
            ->willReturn($this->createMock(PagedResultInterface::class));

        $this->service->search($criteria);
    }
}
