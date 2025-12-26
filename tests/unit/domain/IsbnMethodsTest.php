<?php

declare(strict_types=1);

namespace tests\unit\domain;

use app\domain\exceptions\DomainException;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class IsbnMethodsTest extends Unit
{
    public function testIsbn10ToString(): void
    {
        $isbn = new Isbn('0-306-40615-2');
        $this->assertSame('0306406152', (string)$isbn);
    }

    public function testIsbn13Normalized(): void
    {
        $isbn = new Isbn('978 3 16 148410 0');
        $this->assertSame('9783161484100', $isbn->value);
    }

    public function testInvalidIsbn10Checksum(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('0-306-40615-3');
    }
}
