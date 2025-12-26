<?php

declare(strict_types=1);

namespace tests\unit\presentation\mappers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchResponse;
use app\presentation\mappers\AuthorSelect2Mapper;
use Codeception\Test\Unit;

final class AuthorSelect2MapperTest extends Unit
{
    private AuthorSelect2Mapper $mapper;

    protected function _before(): void
    {
        $this->mapper = new AuthorSelect2Mapper();
    }

    public function testEmptyResult(): void
    {
        $result = $this->mapper->emptyResult();

        $this->assertSame([
            'results' => [],
            'pagination' => ['more' => false],
        ], $result);
    }

    public function testMapToSelect2(): void
    {
        $response = new AuthorSearchResponse(
            items: [
                new AuthorReadDto(1, 'Author 1'),
                new AuthorReadDto(2, 'Author 2'),
            ],
            total: 10,
            page: 1,
            pageSize: 2
        );

        $result = $this->mapper->mapToSelect2($response);

        $this->assertSame([
            'results' => [
                ['id' => 1, 'text' => 'Author 1'],
                ['id' => 2, 'text' => 'Author 2'],
            ],
            'pagination' => ['more' => true],
        ], $result);
    }

    public function testMapToSelect2NoMoreResults(): void
    {
        $response = new AuthorSearchResponse(
            items: [new AuthorReadDto(1, 'Author 1')],
            total: 1,
            page: 1,
            pageSize: 10
        );

        $result = $this->mapper->mapToSelect2($response);

        $this->assertFalse($result['pagination']['more']);
    }
}
