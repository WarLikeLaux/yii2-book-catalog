<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\observability;

use app\infrastructure\services\observability\OtelSpan;
use Codeception\Test\Unit;
use OpenTelemetry\API\Trace\SpanInterface as OtelApiSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use RuntimeException;

final class OtelSpanTest extends Unit
{
    public function testSetAttribute(): void
    {
        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('setAttribute')
            ->with('key', 'value');

        $span = new OtelSpan($mock);
        $this->assertSame($span, $span->setAttribute('key', 'value'));
    }

    public function testSetStatusSuccess(): void
    {
        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('setStatus')
            ->with(StatusCode::STATUS_OK, '');

        $span = new OtelSpan($mock);
        $this->assertSame($span, $span->setStatus(true));
    }

    public function testSetStatusError(): void
    {
        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('setStatus')
            ->with(StatusCode::STATUS_ERROR, 'failed');

        $span = new OtelSpan($mock);
        $this->assertSame($span, $span->setStatus(false, 'failed'));
    }

    public function testRecordException(): void
    {
        $exception = new RuntimeException('Test exception');

        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('recordException')
            ->with($exception);

        $mock->expects($this->once())
            ->method('setStatus')
            ->with(StatusCode::STATUS_ERROR, 'Test exception');

        $span = new OtelSpan($mock);
        $this->assertSame($span, $span->recordException($exception));
    }

    public function testEnd(): void
    {
        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('end');

        $span = new OtelSpan($mock);
        $span->end();
    }

    public function testEndWithScopeAndClosure(): void
    {
        $mock = $this->createMock(OtelApiSpanInterface::class);
        $mock->expects($this->once())
            ->method('end');

        $scopeMock = $this->createMock(ScopeInterface::class);
        $scopeMock->expects($this->once())
            ->method('detach');

        $closureCalled = false;
        $closure = static function () use (&$closureCalled): void {
            $closureCalled = true;
        };

        $span = new OtelSpan($mock, $scopeMock, $closure);
        $span->end();

        $this->assertTrue($closureCalled);
    }
}
