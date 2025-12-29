<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\observability;

use app\infrastructure\services\observability\NullSpan;
use Codeception\Test\Unit;
use RuntimeException;

final class NullSpanTest extends Unit
{
    private NullSpan $span;

    protected function _before(): void
    {
        $this->span = new NullSpan();
    }

    public function testSetAttributeReturnsSelf(): void
    {
        $result = $this->span->setAttribute('key', 'value');

        $this->assertSame($this->span, $result);
    }

    public function testSetAttributeWithDifferentValueTypesReturnsSelf(): void
    {
        $result1 = $this->span->setAttribute('string_key', 'value');
        $result2 = $this->span->setAttribute('int_key', 42);
        $result3 = $this->span->setAttribute('float_key', 3.14);
        $result4 = $this->span->setAttribute('bool_key', true);

        $this->assertSame($this->span, $result1);
        $this->assertSame($this->span, $result2);
        $this->assertSame($this->span, $result3);
        $this->assertSame($this->span, $result4);
    }

    public function testSetStatusWithOkReturnsSelf(): void
    {
        $result = $this->span->setStatus(true);

        $this->assertSame($this->span, $result);
    }

    public function testSetStatusWithErrorReturnsSelf(): void
    {
        $result = $this->span->setStatus(false, 'Error occurred');

        $this->assertSame($this->span, $result);
    }

    public function testRecordExceptionReturnsSelf(): void
    {
        $exception = new RuntimeException('Test exception');

        $result = $this->span->recordException($exception);

        $this->assertSame($this->span, $result);
    }

    public function testEndDoesNotThrow(): void
    {
        $this->span->end();

        $this->assertTrue(true);
    }

    public function testFluentInterface(): void
    {
        $result = $this->span
            ->setAttribute('method', 'GET')
            ->setAttribute('status', 200)
            ->setStatus(true)
            ->recordException(new RuntimeException('Test'))
            ->setStatus(false, 'Failed');

        $this->assertSame($this->span, $result);
    }
}
