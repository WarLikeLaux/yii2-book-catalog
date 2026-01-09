<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\infrastructure\queries\BaseQueryService;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use LogicException;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\Connection;

final class BaseQueryServiceTest extends Unit
{
    public function testExistsWithActiveQueryAndExcludeId(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id'];
            }
        };
        $query->modelClass = $modelClass::class;

        $query->expects($this->once())
            ->method('andWhere')
            ->with(['<>', 'id', 123])
            ->willReturnSelf();

        $query->method('exists')->willReturn(true);

        $this->assertTrue($service->checkExists($query, 123));
    }

    public function testExistsWithNullExcludeId(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQueryInterface::class);
        $query->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assertFalse($service->checkExists($query, null));
    }

    public function testExistsThrowsLogicExceptionForNonActiveQuery(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, int $excludeId): void
            {
                $this->exists($query, $excludeId);
            }
        };

        $nonActiveQuery = $this->makeEmpty(ActiveQueryInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Query must be an instance of ActiveQuery to support dynamic primary key exclusion');

        $service->checkExists($nonActiveQuery, 1);
    }

    public function testExistsWithNonActiveRecordModelClass(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $query->modelClass = \stdClass::class;
        $query->expects($this->once())->method('exists')->willReturn(true);

        $this->assertTrue($service->checkExists($query, 123));
    }

    public function testExistsThrowsLogicExceptionForCompositePkWithScalarExcludeId(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id', 'category_id'];
            }
        };
        $query->modelClass = $modelClass::class;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('excludeId must be an array for composite primary keys');

        $service->checkExists($query, 123);
    }

    public function testExistsThrowsLogicExceptionForCompositePkWithMissingPart(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id', 'category_id'];
            }
        };
        $query->modelClass = $modelClass::class;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing composite PK part: category_id');

        $service->checkExists($query, ['id' => 123]);
    }

    public function testExistsWithSinglePkAndArrayExcludeId(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id'];
            }
        };
        $query->modelClass = $modelClass::class;

        $query->expects($this->once())
            ->method('andWhere')
            ->with(['<>', 'id', 123])
            ->willReturnSelf();

        $query->method('exists')->willReturn(true);

        $this->assertTrue($service->checkExists($query, ['id' => 123]));
    }

    public function testExistsThrowsLogicExceptionWhenArrayExcludeIdMissingSinglePk(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id'];
            }
        };
        $query->modelClass = $modelClass::class;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('excludeId must contain the primary key');

        $service->checkExists($query, ['wrong_key' => 123]);
    }

    public function testExistsWithCompositePkSucceeds(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, mixed $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);
        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id', 'cid'];
            }
        };
        $query->modelClass = $modelClass::class;

        $query->expects($this->once())
            ->method('andWhere')
            ->with(['not', ['and', ['=', 'id', 1], ['=', 'cid', 2]]])
            ->willReturnSelf();

        $query->method('exists')->willReturn(true);

        $this->assertTrue($service->checkExists($query, ['id' => 1, 'cid' => 2]));
    }

    public function testExistsThrowsLogicExceptionForModelWithoutPrimaryKey(): void
    {
        $conn = $this->makeEmpty(Connection::class);
        $mapper = $this->makeEmpty(AutoMapperInterface::class);
        $service = new class ($conn, $mapper) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, int $excludeId): void
            {
                $this->exists($query, $excludeId);
            }
        };

        $query = $this->createMock(ActiveQuery::class);

        $modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return [];
            }
        };

        $query->modelClass = $modelClass::class;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must have a primary key');

        $service->checkExists($query, 123);
    }
}
