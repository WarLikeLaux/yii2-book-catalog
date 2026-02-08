<?php

declare(strict_types=1);

namespace tests\unit\application\common\dto;

use app\application\common\dto\PaginationRequest;
use Codeception\Test\Unit;

final class PaginationRequestTest extends Unit
{
    public function testDefaultValues(): void
    {
        $request = new PaginationRequest(null, null);

        $this->assertSame(1, $request->page);
        $this->assertSame(20, $request->limit);
    }

    public function testValidValues(): void
    {
        $request = new PaginationRequest(5, 50);

        $this->assertSame(5, $request->page);
        $this->assertSame(50, $request->limit);
    }

    public function testStringValues(): void
    {
        $request = new PaginationRequest('5', '50');

        $this->assertSame(5, $request->page);
        $this->assertSame(50, $request->limit);
    }

    public function testPageLowerLimit(): void
    {
        $request = new PaginationRequest(0, 20);
        $this->assertSame(1, $request->page);

        $request = new PaginationRequest(-5, 20);
        $this->assertSame(1, $request->page);
    }

    public function testPageSizeLowerLimit(): void
    {
        $request = new PaginationRequest(1, 0);
        $this->assertSame(1, $request->limit);

        $request = new PaginationRequest(1, -5);
        $this->assertSame(1, $request->limit);
    }

    public function testPageSizeUpperLimit(): void
    {
        $request = new PaginationRequest(1, 101);
        $this->assertSame(100, $request->limit);

        $request = new PaginationRequest(1, 1000);
        $this->assertSame(100, $request->limit);
    }

    public function testGarbageValues(): void
    {
        $request = new PaginationRequest('garbage', 'trash');

        $this->assertSame(1, $request->page);
        $this->assertSame(20, $request->limit);
    }

    public function testCustomDefaultLimit(): void
    {
        $request = new PaginationRequest(null, null, 9);

        $this->assertSame(1, $request->page);
        $this->assertSame(9, $request->limit);
    }
}
