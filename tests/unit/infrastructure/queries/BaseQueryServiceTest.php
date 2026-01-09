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
        $service = $this->createService();

        $query = $this->createMock(ActiveQuery::class);
        $query->modelClass = new class extends ActiveRecord {
            public static function primaryKey(): array
            {
                return ['id'];
            }
        };

        $query->expects($this->once())
            ->method('andWhere')
            ->with(['<>', 'id', 123])
            ->willReturnSelf();

        $query->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->assertTrue($service->checkExists($query, 123));
    }

    public function testExistsWithNullExcludeId(): void
    {
        $service = $this->createService();

        $query = $this->createMock(ActiveQueryInterface::class);
        $query->expects($this->once())
            ->method('exists')
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

    public function testExistsThrowsLogicExceptionForModelWithoutPrimaryKey(): void
    {
        $service = $this->createService();

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

    private function createService(): BaseQueryService
    {
        return new class (
            $this->makeEmpty(Connection::class),
            $this->makeEmpty(AutoMapperInterface::class)
        ) extends BaseQueryService {
            public function checkExists(ActiveQueryInterface $query, ?int $excludeId): bool
            {
                return $this->exists($query, $excludeId);
            }
        };
    }
}
