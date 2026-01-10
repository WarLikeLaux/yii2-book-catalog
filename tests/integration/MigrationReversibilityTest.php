<?php

declare(strict_types=1);

namespace tests\integration;

use app\infrastructure\components\AppDbConnection;
use Codeception\Test\Unit;
use Yii;
use yii\base\Application;
use yii\console\Application as ConsoleApplication;
use yii\console\controllers\MigrateController;
use yii\console\ExitCode;
use yii\db\Connection;

final class MigrationReversibilityTest extends Unit
{
    private ?Application $appBackup = null;

    protected function _before(): void
    {
        $this->appBackup = Yii::$app;
    }

    protected function _after(): void
    {
        Yii::$app = $this->appBackup;
    }

    /**
     * @runInSeparateProcess
     */
    public function testAllMigrationsAreReversible(): void
    {
        $dbName = getenv('DB_MIGRATION_TEST_NAME');

        if (!is_string($dbName) || $dbName === '') {
            $this->markTestSkipped('DB_MIGRATION_TEST_NAME is not set.');
        }

        $db = $this->createDbConnection($dbName);

        $app = new ConsoleApplication([
            'id' => 'migration-test',
            'basePath' => dirname(__DIR__, 2),
            'components' => [
                'db' => $db,
            ],
        ]);

        $controller = new MigrateController('migrate', $app);
        $controller->migrationPath = '@app/migrations';
        $controller->interactive = false;
        $controller->db = $db;

        $upResult = $controller->runAction('up', [0]);
        $this->assertSame(ExitCode::OK, $upResult);

        $downResult = $controller->runAction('down', ['all']);
        $this->assertSame(ExitCode::OK, $downResult);

        $upAgainResult = $controller->runAction('up', [0]);
        $this->assertSame(ExitCode::OK, $upAgainResult);
    }

    private function createDbConnection(string $dbName): Connection
    {
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'pgsql') {
            $host = getenv('PGSQL_DB_HOST') ?: 'pgsql';
            $port = getenv('PGSQL_DB_PORT') ?: '5432';
        } else {
            $host = getenv('MYSQL_DB_HOST') ?: 'db';
            $port = getenv('MYSQL_DB_PORT') ?: '3306';
        }

        $username = getenv('DB_USER') ?: 'yii2';
        $password = getenv('DB_PASSWORD') ?: 'secret';

        return new AppDbConnection([
            'dsn' => "{$driver}:host={$host};port={$port};dbname={$dbName}",
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8',
        ]);
    }
}
