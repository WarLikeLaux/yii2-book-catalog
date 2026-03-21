<?php

declare(strict_types=1);

namespace tests\unit\application\common\values;

use app\application\common\values\AuthorIdCollection;
use app\domain\exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class AuthorIdCollectionTest extends TestCase
{
    public function testFromArrayAcceptsValidIds(): void
    {
        $collection = AuthorIdCollection::fromArray([1, 2, 3]);

        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function testFromArrayDeduplicatesIds(): void
    {
        $collection = AuthorIdCollection::fromArray([5, 5, 3, 5, 3]);

        $this->assertSame([5, 3], $collection->toArray());
    }

    public function testFromArrayAcceptsEmptyArray(): void
    {
        $collection = AuthorIdCollection::fromArray([]);

        $this->assertSame([], $collection->toArray());
    }

    public function testFromArrayThrowsOnNonIntValue(): void
    {
        $this->expectException(ValidationException::class);

        AuthorIdCollection::fromArray([1, 'abc']);
    }

    public function testFromArrayThrowsOnZero(): void
    {
        $this->expectException(ValidationException::class);

        AuthorIdCollection::fromArray([0]);
    }

    public function testFromArrayThrowsOnNegativeId(): void
    {
        $this->expectException(ValidationException::class);

        AuthorIdCollection::fromArray([-5]);
    }

    public function testFromArrayThrowsOnStringDigit(): void
    {
        $this->expectException(ValidationException::class);

        AuthorIdCollection::fromArray(['2']);
    }

    public function testFromArrayThrowsOnBoolValue(): void
    {
        $this->expectException(ValidationException::class);

        /** @phpstan-ignore argument.type */
        AuthorIdCollection::fromArray([true]);
    }
}
