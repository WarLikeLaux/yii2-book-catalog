<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\ValidationException;
use app\domain\values\AuthorId;
use PHPUnit\Framework\TestCase;

final class AuthorIdTest extends TestCase
{
    public function testCreatesWithValidId(): void
    {
        $authorId = new AuthorId(42);

        $this->assertSame(42, $authorId->value);
    }

    public function testThrowsOnZero(): void
    {
        $this->expectException(ValidationException::class);

        new AuthorId(0);
    }

    public function testThrowsOnNegative(): void
    {
        $this->expectException(ValidationException::class);

        new AuthorId(-1);
    }

    public function testEquals(): void
    {
        $a = new AuthorId(1);
        $b = new AuthorId(1);
        $c = new AuthorId(2);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $authorId = new AuthorId(42);

        $this->assertSame('42', (string) $authorId);
    }
}
