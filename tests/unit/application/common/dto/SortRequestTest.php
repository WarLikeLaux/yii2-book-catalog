<?php

declare(strict_types=1);

namespace tests\unit\application\common\dto;

use app\application\common\dto\SortDirection;
use app\application\common\dto\SortRequest;
use PHPUnit\Framework\TestCase;

final class SortRequestTest extends TestCase
{
    public function testFromRequestWithAscField(): void
    {
        $sort = SortRequest::fromRequest('title');

        $this->assertNotNull($sort);
        $this->assertSame('title', $sort->field);
        $this->assertSame(SortDirection::ASC, $sort->direction);
    }

    public function testFromRequestWithDescField(): void
    {
        $sort = SortRequest::fromRequest('-year');

        $this->assertNotNull($sort);
        $this->assertSame('year', $sort->field);
        $this->assertSame(SortDirection::DESC, $sort->direction);
    }

    public function testFromRequestWithNullReturnsNull(): void
    {
        $this->assertNull(SortRequest::fromRequest(null));
    }

    public function testFromRequestWithEmptyStringReturnsNull(): void
    {
        $this->assertNull(SortRequest::fromRequest(''));
    }

    public function testSortDirectionToSortOrderAsc(): void
    {
        $this->assertSame(SORT_ASC, SortDirection::ASC->toSortOrder());
    }

    public function testSortDirectionToSortOrderDesc(): void
    {
        $this->assertSame(SORT_DESC, SortDirection::DESC->toSortOrder());
    }
}
