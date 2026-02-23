<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\observability;

use app\infrastructure\services\observability\OtelSpan;
use app\infrastructure\services\observability\OtelTracer;
use Codeception\Test\Unit;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelApiSpanInterface;
use OpenTelemetry\API\Trace\TracerInterface as OtelApiTracerInterface;
use ReflectionClass;

final class OtelTracerTest extends Unit
{
    public function testStartSpan(): void
    {
        $apiTracerMock = $this->createMock(OtelApiTracerInterface::class);
        $spanBuilderMock = $this->createMock(SpanBuilderInterface::class);
        $otelSpanMock = $this->createMock(OtelApiSpanInterface::class);

        $reflection = new ReflectionClass(OtelTracer::class);
        $tracer = $reflection->newInstanceWithoutConstructor();

        $property = $reflection->getProperty('tracer');
        $property->setAccessible(true);
        $property->setValue($tracer, $apiTracerMock);

        $apiTracerMock->expects($this->once())
            ->method('spanBuilder')
            ->with('test_span')
            ->willReturn($spanBuilderMock);

        $spanBuilderMock->expects($this->once())
            ->method('setAttribute')
            ->with('key', 'value')
            ->willReturnSelf();

        $spanBuilderMock->expects($this->once())
            ->method('startSpan')
            ->willReturn($otelSpanMock);

        $span = $tracer->startSpan('test_span', ['key' => 'value', 'obj' => new \stdClass()]);

        $this->assertInstanceOf(OtelSpan::class, $span);
        $this->assertSame($span, $tracer->activeSpan());
    }
}
