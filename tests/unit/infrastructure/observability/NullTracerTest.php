<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\observability;

use app\infrastructure\services\observability\NullSpan;
use app\infrastructure\services\observability\NullTracer;
use Codeception\Test\Unit;

final class NullTracerTest extends Unit
{
    private NullTracer $tracer;

    protected function _before(): void
    {
        $this->tracer = new NullTracer();
    }

    public function testStartSpanReturnsNullSpan(): void
    {
        $span = $this->tracer->startSpan('test-span');

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function testStartSpanWithAttributesReturnsNullSpan(): void
    {
        $span = $this->tracer->startSpan('test-span', [
            'http.method' => 'GET',
            'http.status_code' => 200,
        ]);

        $this->assertInstanceOf(NullSpan::class, $span);
    }

    public function testActiveSpanReturnsNull(): void
    {
        $activeSpan = $this->tracer->activeSpan();

        $this->assertNull($activeSpan);
    }

    public function testActiveSpanReturnsNullEvenAfterStartSpan(): void
    {
        $this->tracer->startSpan('test-span');

        $activeSpan = $this->tracer->activeSpan();

        $this->assertNull($activeSpan);
    }

    public function testFlushDoesNotThrow(): void
    {
        $this->tracer->flush();

        $this->assertTrue(true);
    }
}
