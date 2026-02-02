<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\application\common\values\AuthorIdCollection;
use Codeception\Test\Unit;

final class AuthorIdCollectionTest extends Unit
{
    public function testFromMixedNormalizesNullToEmptyArray(): void
    {
        $collection = AuthorIdCollection::fromMixed(null);

        $this->assertSame([], $collection->toArray());
    }

    public function testFromMixedWrapsScalarValue(): void
    {
        $collection = AuthorIdCollection::fromMixed('5');

        $this->assertSame([5], $collection->toArray());
    }

    public function testFromArraySkipsInvalidValues(): void
    {
        $collection = AuthorIdCollection::fromArray([1, '2', 'foo', 0, -3, true, [], new \stdClass()]);

        $this->assertSame([1, 2], $collection->toArray());
    }

    public function testFromArrayKeepsValidValuesAfterInvalidOnes(): void
    {
        $collection = AuthorIdCollection::fromArray(['nope', 7]);

        $this->assertSame([7], $collection->toArray());
    }

    public function testFromArraySkipsNonStringTypesAndKeepsFollowing(): void
    {
        $collection = AuthorIdCollection::fromArray([true, 5]);

        $this->assertSame([5], $collection->toArray());
    }

    public function testFromArraySkipsNonPositiveValuesAndKeepsFollowing(): void
    {
        $collection = AuthorIdCollection::fromArray([0, -1, 3]);

        $this->assertSame([3], $collection->toArray());
    }
}
