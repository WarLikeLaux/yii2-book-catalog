<?php

declare(strict_types=1);

namespace tests\unit\presentation\traits;

use app\presentation\common\traits\HtmxDetectionTrait;
use PHPUnit\Framework\TestCase;
use yii\web\HeaderCollection;
use yii\web\Request;

final class HtmxDetectionTraitTest extends TestCase
{
    public function testIsHtmxRequestReturnsTrueWhenHeaderPresent(): void
    {
        $trait = $this->createTraitInstance(['HX-Request' => 'true']);
        $this->assertTrue($trait->callIsHtmxRequest());
    }

    public function testIsHtmxRequestReturnsFalseWhenHeaderMissing(): void
    {
        $trait = $this->createTraitInstance([]);
        $this->assertFalse($trait->callIsHtmxRequest());
    }

    public function testGetHtmxTriggerReturnsValue(): void
    {
        $trait = $this->createTraitInstance(['HX-Trigger' => 'search-form']);
        $this->assertSame('search-form', $trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsNullWhenMissing(): void
    {
        $trait = $this->createTraitInstance([]);
        $this->assertNull($trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsFirstValueWhenArray(): void
    {
        $trait = $this->createTraitInstance(['HX-Trigger' => ['first', 'second']]);
        $this->assertSame('first', $trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTargetReturnsValue(): void
    {
        $trait = $this->createTraitInstance(['HX-Target' => 'book-list']);
        $this->assertSame('book-list', $trait->callGetHtmxTarget());
    }

    public function testGetHtmxTargetReturnsNullWhenMissing(): void
    {
        $trait = $this->createTraitInstance([]);
        $this->assertNull($trait->callGetHtmxTarget());
    }

    public function testGetHtmxTargetReturnsFirstValueWhenArray(): void
    {
        $trait = $this->createTraitInstance(['HX-Target' => ['first', 'second']]);
        $this->assertSame('first', $trait->callGetHtmxTarget());
    }

    public function testGetHtmxTriggerReturnsNullWhenArrayEmpty(): void
    {
        $trait = $this->createTraitInstanceWithHeaderValue([]);
        $this->assertNull($trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsFirstValueWhenMockedArray(): void
    {
        $trait = $this->createTraitInstanceWithHeaderValue(['first', 'second']);
        $this->assertSame('first', $trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsScalarWhenArrayFirstIsScalar(): void
    {
        $trait = $this->createTraitInstanceWithHeaderValue([123, 'second']);
        $this->assertSame('123', $trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsScalarWhenNonStringValue(): void
    {
        $trait = $this->createTraitInstanceWithHeaderValue(456);
        $this->assertSame('456', $trait->callGetHtmxTrigger());
    }

    public function testGetHtmxTriggerReturnsNullWhenNonScalarValue(): void
    {
        $trait = $this->createTraitInstanceWithHeaderValue(new \stdClass());
        $this->assertNull($trait->callGetHtmxTrigger());
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    private function createTraitInstance(array $headers): object
    {
        $headerCollection = new HeaderCollection();

        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $headerCollection->add($name, $v);
                }
            } else {
                $headerCollection->set($name, $value);
            }
        }

        $request = $this->createStub(Request::class);
        $request->method('getHeaders')->willReturn($headerCollection);

        return new class ($request) {
            use HtmxDetectionTrait {
                isHtmxRequest as public callIsHtmxRequest;
                getHtmxTrigger as public callGetHtmxTrigger;
                getHtmxTarget as public callGetHtmxTarget;
            }

            public Request $request;

            public function __construct(Request $request)
            {
                $this->request = $request;
            }
        };
    }

    private function createTraitInstanceWithHeaderValue(mixed $value): object
    {
        $headers = $this->createStub(HeaderCollection::class);
        $headers->method('get')
            ->willReturn($value);

        $request = $this->createStub(Request::class);
        $request->method('getHeaders')->willReturn($headers);

        return new class ($request) {
            use HtmxDetectionTrait {
                getHtmxTrigger as public callGetHtmxTrigger;
            }

            public Request $request;

            public function __construct(Request $request)
            {
                $this->request = $request;
            }
        };
    }
}
