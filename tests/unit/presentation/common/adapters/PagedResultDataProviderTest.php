<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\adapters;

use app\application\common\dto\PaginationDto;
use app\application\ports\PagedResultInterface;
use app\presentation\common\adapters\PagedResultDataProvider;
use Codeception\Test\Unit;

final class PagedResultDataProviderTest extends Unit
{
    public function testPrepareModels(): void
    {
        $models = [['id' => 1, 'name' => 'test']];
        $result = $this->createMockResult($models, 1, new PaginationDto(1, 10, 1, 1));

        $provider = new PagedResultDataProvider($result);

        $this->assertSame($models, $provider->getModels());
    }

    public function testPrepareKeysFromArrayWithId(): void
    {
        $models = [
            ['id' => 10, 'name' => 'test1'],
            ['id' => 20, 'name' => 'test2'],
        ];
        $result = $this->createMockResult($models, 2, new PaginationDto(1, 10, 2, 1));

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertSame([10, 20], $keys);
    }

    public function testPrepareKeysFromObjectWithId(): void
    {
        $obj1 = new class {
            public int $id = 10;
        };
        $obj2 = new class {
            public int $id = 20;
        };

        $result = $this->createMockResult([$obj1, $obj2], 2, new PaginationDto(1, 10, 2, 1));

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertSame([10, 20], $keys);
    }

    public function testPrepareKeysWithoutId(): void
    {
        $models = [['name' => 'test1'], ['name' => 'test2']];
        $result = $this->createMockResult($models, 2, new PaginationDto(1, 10, 2, 1));

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertSame([0, 1], $keys);
    }

    public function testPaginationFromDto(): void
    {
        $paginationDto = new PaginationDto(2, 15, 100, 7);
        $result = $this->createMockResult([], 100, $paginationDto);

        $provider = new PagedResultDataProvider($result);
        $pagination = $provider->getPagination();

        $this->assertSame(1, $pagination->getPage());
        $this->assertSame(15, $pagination->getPageSize());
        $this->assertSame(100, $pagination->totalCount);
    }

    public function testNoPaginationWhenDtoIsNull(): void
    {
        $result = $this->createMockResult([], 0, null);

        $provider = new PagedResultDataProvider($result);

        $this->assertFalse($provider->getPagination());
    }

    public function testGetTotalCountFromResult(): void
    {
        $result = $this->createMockResult([], 42, new PaginationDto(1, 10, 42, 5));

        $provider = new PagedResultDataProvider($result);

        $this->assertSame(42, $provider->getTotalCount());
    }

    private function createMockResult(
        array $models,
        int $totalCount,
        ?PaginationDto $pagination
    ): PagedResultInterface {
        $mock = $this->createMock(PagedResultInterface::class);
        $mock->method('getModels')->willReturn($models);
        $mock->method('getTotalCount')->willReturn($totalCount);
        $mock->method('getPagination')->willReturn($pagination);
        return $mock;
    }
}

