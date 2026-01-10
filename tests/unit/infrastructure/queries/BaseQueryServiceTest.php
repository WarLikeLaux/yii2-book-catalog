<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use LogicException;
use tests\_support\TestableBaseQueryService;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\Connection;

final class BaseQueryServiceTest extends Unit
{
    private Connection $conn;

    protected function _before(): void
    {
        $this->conn = $this->createMock(Connection::class);
    }

    public function testExistsWithActiveQueryAndExcludeId(): void
    {
        $service = $this->createService();
        $calls = [];
        $modelClass = $this->createActiveRecordClass(['id']);
        $query = $this->createRecordingActiveQuery($modelClass, true, $calls);

        $this->assertTrue($service->checkExists($query, 123));

        $this->assertSame([
            ['andWhere', ['<>', 'id', 123]],
            ['exists', $this->conn],
        ], $calls);
    }

    public function testExistsWithNullExcludeId(): void
    {
        $service = $this->createService();

        $query = $this->createMock(ActiveQueryInterface::class);
        $query->expects($this->once())
            ->method('exists')
            ->with($this->identicalTo($this->conn))
            ->willReturn(false);

        $this->assertFalse($service->checkExists($query, null));
    }

    public function testExistsThrowsLogicExceptionForNonActiveQuery(): void
    {
        $service = $this->createService();

        $nonActiveQuery = $this->makeEmpty(ActiveQueryInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Query must be an instance of ActiveQuery to support dynamic primary key exclusion');

        $service->checkExists($nonActiveQuery, 1);
    }

    public function testExistsWithNonActiveRecordModelClass(): void
    {
        $service = $this->createService();

        $query = $this->createMock(ActiveQuery::class);
        $query->modelClass = \stdClass::class;
        $query->expects($this->once())
            ->method('exists')
            ->with($this->identicalTo($this->conn))
            ->willReturn(true);

        $this->assertTrue($service->checkExists($query, 123));
    }

    public function testExistsThrowsLogicExceptionForCompositePkWithScalarExcludeId(): void
    {
        $service = $this->createService();
        $query = $this->createActiveQueryWithPrimaryKey(['id', 'category_id']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('excludeId must be an array for composite primary keys');

        $service->checkExists($query, 123);
    }

    public function testExistsThrowsLogicExceptionForCompositePkWithMissingPart(): void
    {
        $service = $this->createService();
        $query = $this->createActiveQueryWithPrimaryKey(['id', 'category_id']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing composite PK part: category_id');

        $service->checkExists($query, ['id' => 123]);
    }

    public function testExistsWithSinglePkAndArrayExcludeId(): void
    {
        $service = $this->createService();
        $calls = [];
        $modelClass = $this->createActiveRecordClass(['id']);
        $query = $this->createRecordingActiveQuery($modelClass, true, $calls);

        $this->assertTrue($service->checkExists($query, ['id' => 123]));

        $this->assertSame([
            ['andWhere', ['<>', 'id', 123]],
            ['exists', $this->conn],
        ], $calls);
    }

    public function testExistsThrowsLogicExceptionWhenArrayExcludeIdMissingSinglePk(): void
    {
        $service = $this->createService();
        $query = $this->createActiveQueryWithPrimaryKey(['id']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('excludeId must contain the primary key');

        $service->checkExists($query, ['wrong_key' => 123]);
    }

    public function testExistsWithCompositePkSucceeds(): void
    {
        $service = $this->createService();
        $calls = [];
        $modelClass = $this->createActiveRecordClass(['id', 'cid']);
        $query = $this->createRecordingActiveQuery($modelClass, true, $calls);

        $this->assertTrue($service->checkExists($query, ['id' => 1, 'cid' => 2]));

        $this->assertSame([
            ['andWhere', ['not', ['and', ['=', 'id', 1], ['=', 'cid', 2]]]],
            ['exists', $this->conn],
        ], $calls);
    }

    public function testExistsThrowsLogicExceptionForModelWithoutPrimaryKey(): void
    {
        $service = $this->createService();
        $query = $this->createActiveQueryWithPrimaryKey([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must have a primary key');

        $service->checkExists($query, 123);
    }

    private function createService(): object
    {
        $mapper = $this->makeEmpty(AutoMapperInterface::class);

        return new TestableBaseQueryService($this->conn, $mapper);
    }

    private function createActiveQueryWithPrimaryKey(array $primaryKey): ActiveQuery
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->modelClass = $this->createActiveRecordClass($primaryKey);

        return $query;
    }

    private function createActiveRecordClass(array $primaryKey): string
    {
        $record = new class extends ActiveRecord {
            public static array $primaryKey = [];

            public static function primaryKey(): array
            {
                return self::$primaryKey;
            }
        };

        $className = $record::class;
        $className::$primaryKey = $primaryKey;

        return $className;
    }

    /**
     * @param array<int, array<int, mixed>> $calls
     */
    private function createRecordingActiveQuery(string $modelClass, bool $existsResult, array &$calls): ActiveQuery
    {
        return new class ($modelClass, $existsResult, $calls) extends ActiveQuery {
            private bool $existsResult;

            /** @var array<int, array<int, mixed>> */
            private array $calls;

            /**
             * @param array<int, array<int, mixed>> $calls
             */
            public function __construct(string $modelClass, bool $existsResult, array &$calls)
            {
                $this->existsResult = $existsResult;
                $this->calls =& $calls;
                parent::__construct($modelClass);
            }

            public function andWhere($condition, $params = []): static
            {
                unset($params);
                $this->calls[] = ['andWhere', $condition];
                return $this;
            }

            public function exists($db = null): bool
            {
                $this->calls[] = ['exists', $db];
                return $this->existsResult;
            }
        };
    }
}
