<?php

declare(strict_types=1);

namespace tests\unit\presentation\mappers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchResponse;
use app\presentation\authors\mappers\AuthorSelect2Mapper;
use Codeception\Test\Unit;

final class AuthorSelect2MapperTest extends Unit
{
    private AuthorSelect2Mapper $mapper;

    protected function _before(): void
    {
        $this->mapper = new AuthorSelect2Mapper();
    }

    public function testEmptyResultReturnsCorrectStructure(): void
    {
        $result = $this->mapper->emptyResult();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEmpty($result['results']);
        $this->assertFalse($result['pagination']['more']);
    }

    public function testMapToSelect2WithItems(): void
    {
        $response = new AuthorSearchResponse(
            items: [
                new AuthorReadDto(1, 'Author One'),
                new AuthorReadDto(2, 'Author Two'),
            ],
            page: 1,
            pageSize: 10,
            total: 2,
        );

        $result = $this->mapper->mapToSelect2($response);

        $this->assertCount(2, $result['results']);
        $this->assertEquals(1, $result['results'][0]['id']);
        $this->assertEquals('Author One', $result['results'][0]['text']);
        $this->assertFalse($result['pagination']['more']);
    }

    public function testMapToSelect2WithPaginationMore(): void
    {
        $response = new AuthorSearchResponse(
            items: [new AuthorReadDto(1, 'Author')],
            page: 1,
            pageSize: 10,
            total: 25,
        );

        $result = $this->mapper->mapToSelect2($response);

        $this->assertTrue($result['pagination']['more']);
    }
}
