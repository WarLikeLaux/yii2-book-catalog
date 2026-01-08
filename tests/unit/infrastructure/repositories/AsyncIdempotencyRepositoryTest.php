<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\infrastructure\persistence\AsyncIdempotencyLog;
use app\infrastructure\repositories\AsyncIdempotencyRepository;
use Codeception\Test\Unit;

final class AsyncIdempotencyRepositoryTest extends Unit
{
    private const int DEFAULT_TTL = 172800;
    private const int OLD_TIMESTAMP_OFFSET = 200000;

    private AsyncIdempotencyRepository $repository;

    protected function _before(): void
    {
        $this->repository = new AsyncIdempotencyRepository();
        AsyncIdempotencyLog::deleteAll();
    }

    public function testAcquireReturnsTrue(): void
    {
        $this->assertTrue($this->repository->acquire('test-key'));
    }

    public function testAcquireReturnsFalseOnDuplicate(): void
    {
        $this->assertTrue($this->repository->acquire('test-key'));
        $this->assertFalse($this->repository->acquire('test-key'));
    }

    public function testReleaseDeletesRecord(): void
    {
        $this->repository->acquire('test-key');

        $this->repository->release('test-key');

        $this->assertTrue($this->repository->acquire('test-key'));
    }

    public function testReleaseNonExistentKeyDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $this->repository->release('non-existent');
    }

    public function testDeleteExpiredRemovesOldRecords(): void
    {
        $model = new AsyncIdempotencyLog();
        $model->idempotency_key = 'old-key';
        $model->created_at = time() - self::OLD_TIMESTAMP_OFFSET;
        $model->save();

        $this->repository->acquire('new-key');

        $deleted = $this->repository->deleteExpired(172800);

        $this->assertSame(1, $deleted);
        $this->assertFalse($this->repository->acquire('new-key'));
    }

    public function testDeleteExpiredReturnsZeroWhenNoExpired(): void
    {
        $this->repository->acquire('new-key');

        $deleted = $this->repository->deleteExpired(172800);

        $this->assertSame(0, $deleted);
    }
}
