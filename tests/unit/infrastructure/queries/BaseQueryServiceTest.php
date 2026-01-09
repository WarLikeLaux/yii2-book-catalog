<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\infrastructure\queries\BaseQueryService;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use LogicException;
use yii\db\ActiveQueryInterface;
use yii\db\Connection;

final class BaseQueryServiceTest extends Unit
{
    public function testExistsThrowsLogicExceptionForNonActiveQuery(): void
    {
        $service = new class (
            $this->makeEmpty(Connection::class),
            $this->makeEmpty(AutoMapperInterface::class)
        ) extends BaseQueryService {
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
}
