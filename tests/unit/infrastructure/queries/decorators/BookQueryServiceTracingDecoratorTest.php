<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries\decorators;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\application\ports\TracerInterface;
use app\domain\specifications\BookSpecificationInterface;
use app\infrastructure\queries\decorators\BookQueryServiceTracingDecorator;
use Codeception\Test\Unit;

final class BookQueryServiceTracingDecoratorTest extends Unit
{
    public function testFindByIdDelegatesToServiceWithTracing(): void
    {
        $expected = new BookReadDto(
            id: 1,
            title: 'Test',
            year: 2024,
            description: null,
            isbn: '9783161484100',
            authorIds: [],
        );

        $service = $this->createMock(BookQueryServiceInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::findById',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_, callable $callback): ?BookReadDto => $callback());

        $service->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($expected);

        $result = $decorator->findById(1);

        $this->assertSame($expected, $result);
    }

    public function testFindByIdWithAuthorsDelegatesToServiceWithTracing(): void
    {
        $expected = new BookReadDto(
            id: 2,
            title: 'With Authors',
            year: 2025,
            description: null,
            isbn: '9780132350884',
            authorIds: [10],
        );

        $service = $this->createMock(BookQueryServiceInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::findByIdWithAuthors',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_, callable $callback): ?BookReadDto => $callback());

        $service->expects($this->once())
            ->method('findByIdWithAuthors')
            ->with(2)
            ->willReturn($expected);

        $result = $decorator->findByIdWithAuthors(2);

        $this->assertSame($expected, $result);
    }

    public function testSearchDelegatesToServiceWithTracing(): void
    {
        $expected = $this->createMock(PagedResultInterface::class);

        $service = $this->createMock(BookQueryServiceInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::search',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_, callable $callback): PagedResultInterface => $callback());

        $service->expects($this->once())
            ->method('search')
            ->with('clean', 1, 10)
            ->willReturn($expected);

        $result = $decorator->search('clean', 1, 10);

        $this->assertSame($expected, $result);
    }

    public function testSearchPublishedDelegatesToServiceWithTracing(): void
    {
        $expected = $this->createMock(PagedResultInterface::class);

        $service = $this->createMock(BookQueryServiceInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::searchPublished',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_, callable $callback): PagedResultInterface => $callback());

        $service->expects($this->once())
            ->method('searchPublished')
            ->with('ddd', 2, 20)
            ->willReturn($expected);

        $result = $decorator->searchPublished('ddd', 2, 20);

        $this->assertSame($expected, $result);
    }

    public function testSearchBySpecificationDelegatesToServiceWithTracing(): void
    {
        $expected = $this->createMock(PagedResultInterface::class);
        $specification = $this->createMock(BookSpecificationInterface::class);

        $service = $this->createMock(BookQueryServiceInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new BookQueryServiceTracingDecorator($service, $tracer);

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'BookQuery::searchBySpecification',
                $this->isType('callable'),
            )
            ->willReturnCallback(static fn(string $_, callable $callback): PagedResultInterface => $callback());

        $service->expects($this->once())
            ->method('searchBySpecification')
            ->with($specification, 3, 15)
            ->willReturn($expected);

        $result = $decorator->searchBySpecification($specification, 3, 15);

        $this->assertSame($expected, $result);
    }
}
