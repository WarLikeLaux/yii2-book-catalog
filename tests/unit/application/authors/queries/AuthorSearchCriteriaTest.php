<?php

declare(strict_types=1);

namespace tests\unit\application\authors\queries;

use app\application\authors\queries\AuthorSearchCriteria;
use Codeception\Test\Unit;

final class AuthorSearchCriteriaTest extends Unit
{
    public function testDefaultValues(): void
    {
        $criteria = new AuthorSearchCriteria();
        $this->assertSame('', $criteria->search);
        $this->assertSame(1, $criteria->page);
        $this->assertSame(20, $criteria->pageSize);
    }
}
