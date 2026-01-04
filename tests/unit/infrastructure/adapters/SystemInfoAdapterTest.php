<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\SystemInfoAdapter;
use PHPUnit\Framework\TestCase;
use yii\db\Connection;

final class SystemInfoAdapterTest extends TestCase
{
    public function testGetInfo(): void
    {
        $db = new class extends Connection {
            public $driverName = 'mysql';

            public function getSlavePdo($fallbackToMaster = true)
            {
                return null;
            }
        };

        $adapter = new SystemInfoAdapter($db);
        $info = $adapter->getInfo();

        $this->assertEquals(PHP_VERSION, $info->phpVersion);
        $this->assertEquals(\Yii::getVersion(), $info->yiiVersion);
        $this->assertEquals('MYSQL', $info->dbDriver);
        $this->assertEquals('unknown', $info->dbVersion);
    }

    public function testGetDbVersionReturnsUnknownOnException(): void
    {
        $db = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSlavePdo'])
            ->getMock();

        $db->method('getSlavePdo')->willThrowException(new \Exception('DB Error'));
        $db->driverName = 'pgsql';

        $adapter = new SystemInfoAdapter($db);
        $info = $adapter->getInfo();

        $this->assertEquals('unknown', $info->dbVersion);
        $this->assertEquals('PGSQL', $info->dbDriver);
    }
}
