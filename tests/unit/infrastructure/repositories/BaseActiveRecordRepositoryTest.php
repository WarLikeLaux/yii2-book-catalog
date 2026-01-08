<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\StaleDataException;
use app\infrastructure\repositories\BaseActiveRecordRepository;
use Codeception\Test\Unit;
use RuntimeException;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

final class BaseActiveRecordRepositoryTest extends Unit
{
    private object $repository;

    protected function _before(): void
    {
        $this->repository = new class extends BaseActiveRecordRepository {
            public function testPersist(
                ActiveRecord $model,
                ?DomainErrorCode $duplicateError = null,
                string $errorMessage = 'entity.error.save_failed',
            ): void {
                $this->persist($model, $duplicateError, $errorMessage);
            }
        };
    }

    public function testPersistSuccess(): void
    {
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => true,
        ]);

        $this->repository->testPersist($model);
    }

    public function testPersistThrowsRuntimeExceptionOnSaveFailure(): void
    {
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => false,
            'getFirstErrors' => ['error' => 'save failed'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('save failed');

        $this->repository->testPersist($model);
    }

    public function testPersistThrowsStaleDataExceptionOnStaleObject(): void
    {
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => static function () {
                throw new StaleObjectException('Stale object');
            },
        ]);

        $this->expectException(StaleDataException::class);

        $this->repository->testPersist($model);
    }

    public function testPersistThrowsAlreadyExistsExceptionOnDuplicate(): void
    {
        $exception = new IntegrityException('Duplicate entry', ['SQLSTATE[23000]', 1062, 'Duplicate entry']);
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => static function () use ($exception) {
                throw $exception;
            },
        ]);

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionCode(409);

        $this->repository->testPersist($model, DomainErrorCode::AuthorFioExists);
    }

    public function testPersistThrowsGenericAlreadyExistsExceptionOnDuplicateWithoutCode(): void
    {
        $exception = new IntegrityException('Duplicate entry', ['SQLSTATE[23000]', 1062, 'Duplicate entry']);
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => static function () use ($exception) {
                throw $exception;
            },
        ]);

        $this->expectException(AlreadyExistsException::class);

        $this->repository->testPersist($model);
    }

    public function testPersistRethrowsGenericIntegrityException(): void
    {
        $exception = new IntegrityException('Other error', ['SQLSTATE[23000]', 1234, 'Other error']);
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => static function () use ($exception) {
                throw $exception;
            },
        ]);

        $this->expectException(IntegrityException::class);

        $this->repository->testPersist($model);
    }

    public function testPersistThrowsRuntimeExceptionWithDefaultMessageOnSaveFailureWithoutErrors(): void
    {
        $model = $this->makeEmpty(ActiveRecord::class, [
            'save' => false,
            'getFirstErrors' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('entity.error.save_failed');

        $this->repository->testPersist($model);
    }
}
