<?php

declare(strict_types=1);

namespace tests\unit\presentation\adapters;

use app\application\common\dto\PaginationDto;
use app\application\ports\PagedResultInterface;
use app\presentation\common\adapters\PagedResultDataProvider;
use Codeception\Test\Unit;

final class PagedResultDataProviderTest extends Unit
{
    public function testPrepareKeysWithObjectModels(): void
    {
        $models = [
            (object)['id' => 1, 'name' => 'First'],
            (object)['id' => 2, 'name' => 'Second'],
        ];

        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn($models);
        $result->method('getTotalCount')->willReturn(2);
        $result->method('getPagination')->willReturn(null);

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertEquals([1, 2], $keys);
    }

    public function testPrepareKeysWithArrayModels(): void
    {
        $models = [
            ['id' => 10, 'name' => 'First'],
            ['id' => 20, 'name' => 'Second'],
        ];

        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn($models);
        $result->method('getTotalCount')->willReturn(2);
        $result->method('getPagination')->willReturn(null);

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertEquals([10, 20], $keys);
    }

    public function testPrepareKeysWithMixedModels(): void
    {
        $models = [
            (object)['id' => 1],
            ['id' => 2],
            'plain string',
        ];

        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn($models);
        $result->method('getTotalCount')->willReturn(3);
        $result->method('getPagination')->willReturn(null);

        $provider = new PagedResultDataProvider($result);
        $keys = $provider->getKeys();

        $this->assertEquals([1, 2, 2], $keys);
    }

    public function testPaginationFromDto(): void
    {
        $paginationDto = new PaginationDto(
            page: 2,
            pageSize: 20,
            totalCount: 100,
            totalPages: 5,
        );

        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn([]);
        $result->method('getTotalCount')->willReturn(100);
        $result->method('getPagination')->willReturn($paginationDto);

        $provider = new PagedResultDataProvider($result);
        $pagination = $provider->getPagination();

        $this->assertNotFalse($pagination);
        $this->assertEquals(1, $pagination->getPage());
        $this->assertEquals(20, $pagination->getPageSize());
        $this->assertEquals(100, $pagination->totalCount);
    }

    public function testPaginationDisabledWhenNoPaginationDto(): void
    {
        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn([]);
        $result->method('getTotalCount')->willReturn(0);
        $result->method('getPagination')->willReturn(null);

        $provider = new PagedResultDataProvider($result);

        $this->assertFalse($provider->getPagination());
    }

    public function testGetTotalCountDelegatesToResult(): void
    {
        $expectedCount = 42;
        $result = $this->createMock(PagedResultInterface::class);
        $result->method('getModels')->willReturn([]);
        $result->method('getTotalCount')->willReturn($expectedCount);

        $provider = new PagedResultDataProvider($result, [
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        $this->assertEquals($expectedCount, $provider->getTotalCount());
    }
}
