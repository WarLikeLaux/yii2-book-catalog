<?php

declare(strict_types=1);

namespace tests\unit\application\books\queries;

use app\application\books\queries\BookSearchCriteria;
use Codeception\Test\Unit;

final class BookSearchCriteriaTest extends Unit
{
    public function testDefaultValues(): void
    {
        $criteria = new BookSearchCriteria();
        $this->assertSame('', $criteria->globalSearch);
        $this->assertSame(1, $criteria->page);
        $this->assertSame(20, $criteria->pageSize);
    }
}
