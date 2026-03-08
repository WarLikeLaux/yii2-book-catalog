<?php

declare(strict_types=1);

use app\infrastructure\components\AppDbConnection;
use Env\Env;

$driver = Env::get('DB_DRIVER') ?? 'mysql';

[$host, $port] = match ($driver) {
    'pgsql' => [Env::get('PGSQL_DB_HOST') ?? 'pgsql', Env::get('PGSQL_DB_PORT') ?? '5432'],
    default => [Env::get('MYSQL_DB_HOST') ?? 'db', Env::get('MYSQL_DB_PORT') ?? '3306'],
};

$database = Env::get('DB_TEST_NAME') ?? 'yii2basic_test';
$username = Env::get('DB_USER') ?? 'yii2';
$password = Env::get('DB_PASSWORD') ?? 'secret';

return [
    'class' => AppDbConnection::class,
    'dsn' => "{$driver}:host={$host};port={$port};dbname={$database}",
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',
];
