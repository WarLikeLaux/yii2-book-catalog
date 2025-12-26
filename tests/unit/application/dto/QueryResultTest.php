<?php

declare(strict_types=1);

namespace tests\unit\application\dto;

use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use Codeception\Test\Unit;

final class QueryResultTest extends Unit
{
    public function testGetModels(): void
    {
        $models = [['id' => 1], ['id' => 2]];
        $pagination = new PaginationDto(1, 10, 2, 1);
        
        $result = new QueryResult($models, 2, $pagination);
        
        $this->assertSame($models, $result->getModels());
    }

    public function testGetTotalCount(): void
    {
        $pagination = new PaginationDto(1, 10, 42, 5);
        $result = new QueryResult([], 42, $pagination);
        
        $this->assertSame(42, $result->getTotalCount());
    }

    public function testGetPagination(): void
    {
        $pagination = new PaginationDto(2, 15, 100, 7);
        $result = new QueryResult([], 100, $pagination);
        
        $this->assertSame($pagination, $result->getPagination());
    }

    public function testGetPaginationNull(): void
    {
        $result = new QueryResult([], 0);
        
        $this->assertNull($result->getPagination());
    }
}
