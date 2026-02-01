<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\values\AuthorIdCollection;
use Codeception\Test\Unit;

final class AuthorIdCollectionTest extends Unit
{
    public function testFromMixedNormalizesNullToEmptyArray(): void
    {
        $collection = AuthorIdCollection::fromMixed(null, new \stdClass(), []);

        $this->assertSame([], $collection->toArray());
    }

    public function testFromMixedWrapsScalarValue(): void
    {
        $collection = AuthorIdCollection::fromMixed('5', new \stdClass(), []);

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
}
