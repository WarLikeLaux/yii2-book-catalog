<?php

declare(strict_types=1);

namespace app\tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class IsbnMethodsTest extends Unit
{
    public function testInvalidIsbn10Checksum(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('0306406151');
    }

    public function testInvalidIsbn13Prefix(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('1234567890123');
    }

    public function testInvalidLength(): void
    {
        $this->expectException(DomainException::class);
        new Isbn('123');
    }
}