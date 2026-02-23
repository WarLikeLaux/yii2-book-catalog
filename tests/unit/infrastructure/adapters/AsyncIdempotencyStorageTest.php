<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\AsyncIdempotencyStorage;
use app\infrastructure\adapters\SystemClock;
use app\infrastructure\persistence\AsyncIdempotencyLog;
use Codeception\Test\Unit;

final class AsyncIdempotencyStorageTest extends Unit
{
    private const int DEFAULT_TTL = 172800;
    private const int OLD_TIMESTAMP_OFFSET = 200000;

    private AsyncIdempotencyStorage $storage;

    protected function _before(): void
    {
        $this->storage = new AsyncIdempotencyStorage(new SystemClock());
        AsyncIdempotencyLog::deleteAll();
    }

    public function testAcquireReturnsTrue(): void
    {
        $this->assertTrue($this->storage->acquire('test-key'));
    }

    public function testAcquireReturnsFalseOnDuplicate(): void
    {
        $this->assertTrue($this->storage->acquire('test-key'));
        $this->assertFalse($this->storage->acquire('test-key'));
    }

    public function testReleaseDeletesRecord(): void
    {
        $this->storage->acquire('test-key');

        $this->storage->release('test-key');

        $this->assertTrue($this->storage->acquire('test-key'));
    }

    public function testReleaseNonExistentKeyDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $this->storage->release('non-existent');
    }

    public function testDeleteExpiredRemovesOldRecords(): void
    {
        $model = new AsyncIdempotencyLog();
        $model->idempotency_key = 'old-key';
        $model->created_at = time() - self::OLD_TIMESTAMP_OFFSET;
        $model->save();

        $this->storage->acquire('new-key');

        $deleted = $this->storage->deleteExpired(self::DEFAULT_TTL);

        $this->assertSame(1, $deleted);
        $this->assertFalse($this->storage->acquire('new-key'));
    }

    public function testDeleteExpiredReturnsZeroWhenNoExpired(): void
    {
        $this->storage->acquire('new-key');

        $deleted = $this->storage->deleteExpired(self::DEFAULT_TTL);

        $this->assertSame(0, $deleted);
    }
}
