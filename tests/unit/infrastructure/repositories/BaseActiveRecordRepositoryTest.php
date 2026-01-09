<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\domain\entities\Book;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;
use tests\_support\StubActiveRecord;
use tests\_support\StubActiveRecordRepository;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

final class BaseActiveRecordRepositoryTest extends Unit
{
    private const string SAVE_FAILED_MESSAGE = 'save failed';
    private const string DUPLICATE_ENTRY = 'Duplicate entry';
    private const string DUPLICATE_SQLSTATE = 'SQLSTATE[23000]';
    private const int DUPLICATE_CODE = 1062;
    private const string OTHER_ERROR = 'Other error';
    private const int OTHER_ERROR_CODE = 1234;

    private StubActiveRecordRepository $repository;

    protected function _before(): void
    {
        $this->repository = new StubActiveRecordRepository();
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
            'getFirstErrors' => ['error' => self::SAVE_FAILED_MESSAGE],
        ]);

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('error.entity_persist_failed');

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
        $exception = new IntegrityException(self::DUPLICATE_ENTRY, [self::DUPLICATE_SQLSTATE, self::DUPLICATE_CODE, self::DUPLICATE_ENTRY]);
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
        $exception = new IntegrityException(self::DUPLICATE_ENTRY, [self::DUPLICATE_SQLSTATE, self::DUPLICATE_CODE, self::DUPLICATE_ENTRY]);
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
        $exception = new IntegrityException(self::OTHER_ERROR, [self::DUPLICATE_SQLSTATE, self::OTHER_ERROR_CODE, self::OTHER_ERROR]);
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

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('error.entity_persist_failed');

        $this->repository->testPersist($model);
    }

    public function testDeleteEntityRemovesIdentity(): void
    {
        $entityToRemove = $this->createBookEntity(1);
        $otherEntity = $this->createBookEntity(2);
        $storedRecord = new StubActiveRecord();
        $otherRecord = new StubActiveRecord();

        $this->repository->testRegisterIdentity($entityToRemove, $storedRecord);
        $this->repository->testRegisterIdentity($otherEntity, $otherRecord);

        StubActiveRecord::$next = new StubActiveRecord();

        $this->repository->testDeleteEntity(
            $entityToRemove,
            StubActiveRecord::class,
            DomainErrorCode::BookNotFound,
        );

        $this->assertFalse($this->repository->hasIdentity($entityToRemove));
        $this->assertTrue($this->repository->hasIdentity($otherEntity));
    }

    public function testDeleteEntityKeepsUnmatchedIdentities(): void
    {
        $firstEntity = $this->createBookEntity(1);
        $secondEntity = $this->createBookEntity(2);
        $targetEntity = $this->createBookEntity(3);

        $this->repository->testRegisterIdentity($firstEntity, new StubActiveRecord());
        $this->repository->testRegisterIdentity($secondEntity, new StubActiveRecord());

        StubActiveRecord::$next = new StubActiveRecord();

        $this->repository->testDeleteEntity(
            $targetEntity,
            StubActiveRecord::class,
            DomainErrorCode::BookNotFound,
        );

        $this->assertTrue($this->repository->hasIdentity($firstEntity));
        $this->assertTrue($this->repository->hasIdentity($secondEntity));
    }

    private function createBookEntity(int $id): Book
    {
        return Book::reconstitute(
            $id,
            'Test Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Valid description',
            new StoredFileReference('covers/test.jpg'),
            [],
            false,
            1,
        );
    }
}
