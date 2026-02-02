<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\observability;

use app\infrastructure\services\observability\InspectorSpan;
use Codeception\Test\Unit;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use RuntimeException;

final class InspectorSpanTest extends Unit
{
    public function testSetAttributeStoresValue(): void
    {
        $transaction = $this->createTransaction();
        $transaction->addContext('Custom', []);
        $span = new InspectorSpan($transaction);

        $result = $span->setAttribute('db.query', 'SELECT 1');

        $this->assertSame($span, $result);

        /** @var array<string, mixed> $context */
        $context = $transaction->getContext('Custom');
        $this->assertSame('SELECT 1', $context['db.query']);
    }

    public function testSetAttributeFiltersHeaderKey(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);

        $span->setAttribute('X-Header-Auth', 'secret');

        $this->assertEmpty($transaction->context);
    }

    public function testSetAttributeFiltersCookieKey(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);

        $span->setAttribute('session_cookie', 'abc');

        $this->assertEmpty($transaction->context);
    }

    public function testSetStatusSuccessOnTransaction(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);

        $result = $span->setStatus(true);

        $this->assertSame($span, $result);
        $this->assertSame('success', $transaction->result);
    }

    public function testSetStatusErrorOnTransaction(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);

        $span->setStatus(false, 'something failed');

        $this->assertSame('error', $transaction->result);

        /** @var array<string, mixed> $statusContext */
        $statusContext = $transaction->getContext('Status');
        $this->assertSame('something failed', $statusContext['description']);
    }

    public function testSetStatusErrorWithoutDescriptionOnTransaction(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);

        $span->setStatus(false);

        $this->assertSame('error', $transaction->result);
        $this->assertArrayNotHasKey('Status', $transaction->context);
    }

    public function testSetStatusReturnsEarlyForSegment(): void
    {
        $segment = $this->createSegment();
        $span = new InspectorSpan($segment);

        $result = $span->setStatus(false, 'error');

        $this->assertSame($span, $result);
        $this->assertNull($segment->result ?? null);
    }

    public function testRecordExceptionOnTransaction(): void
    {
        $transaction = $this->createTransaction();
        $span = new InspectorSpan($transaction);
        $exception = new RuntimeException('test error', 42);

        $result = $span->recordException($exception);

        $this->assertSame($span, $result);
        $this->assertSame('error', $transaction->result);

        /** @var array<string, mixed> $exContext */
        $exContext = $transaction->getContext('Exception');
        $this->assertSame(RuntimeException::class, $exContext['class']);
        $this->assertSame('test error', $exContext['message']);
        $this->assertSame(42, $exContext['code']);
    }

    public function testRecordExceptionOnSegment(): void
    {
        $segment = $this->createSegment();
        $span = new InspectorSpan($segment);
        $exception = new RuntimeException('seg error');

        $span->recordException($exception);

        /** @var array<string, mixed> $exContext */
        $exContext = $segment->getContext('Exception');
        $this->assertSame(RuntimeException::class, $exContext['class']);
        $this->assertSame('seg error', $exContext['message']);
    }

    public function testEndWithTransaction(): void
    {
        $transaction = $this->createTransaction();
        $transaction->start();
        $span = new InspectorSpan($transaction);

        $originalTimestamp = $transaction->timestamp;

        $span->end();

        $this->assertNotNull($transaction->duration);
        $this->assertEqualsWithDelta($originalTimestamp * 1000, $transaction->timestamp, 1.0);
        $this->assertSame((string)gethostname(), $transaction->host->hostname);
    }

    public function testEndWithSegment(): void
    {
        $transaction = $this->createTransaction();
        $transaction->start();

        $segment = new Segment($transaction, 'process', 'test');
        $segment->start();

        $span = new InspectorSpan($segment);
        $originalTimestamp = $segment->timestamp;
        $originalTransactionTimestamp = $segment->transaction['timestamp'];

        $span->end();

        $this->assertNotNull($segment->duration);
        $this->assertEqualsWithDelta($originalTimestamp * 1000, $segment->timestamp, 1.0);
        $this->assertEqualsWithDelta(
            $originalTransactionTimestamp * 1000,
            $segment->transaction['timestamp'],
            1.0,
        );
    }

    public function testEndWithSegmentWhenTimestampAlreadyConverted(): void
    {
        $transaction = $this->createTransaction();
        $transaction->start();

        $segment = new Segment($transaction, 'process', 'test');
        $segment->start();

        $segment->transaction = array_merge(
            $segment->transaction,
            ['timestamp' => 99999999999.0],
        );

        $span = new InspectorSpan($segment);
        $span->end();

        $this->assertEqualsWithDelta(99999999999.0, $segment->transaction['timestamp'], 0.001);
    }

    private function createTransaction(): Transaction
    {
        return new Transaction('test-transaction');
    }

    private function createSegment(): Segment
    {
        $transaction = $this->createTransaction();
        $transaction->start();

        return (new Segment($transaction, 'process', 'test-segment'))->start();
    }
}
