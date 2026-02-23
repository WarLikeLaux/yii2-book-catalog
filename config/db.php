<?php

declare(strict_types=1);

use app\infrastructure\components\AppDbConnection;

$driver = env('DB_DRIVER', 'mysql');

[$host, $port] = match ($driver) {
    'pgsql' => [env('PGSQL_DB_HOST', 'pgsql'), env('PGSQL_DB_PORT', '5432')],
    default => [env('MYSQL_DB_HOST', 'db'), env('MYSQL_DB_PORT', '3306')],
};

$database = env('DB_NAME', 'yii2basic');
$username = env('DB_USER', 'yii2');
$password = env('DB_PASSWORD', 'secret');

$dsn = "{$driver}:host={$host};port={$port};dbname={$database}";

return [
    'class' => AppDbConnection::class,
    'dsn' => $dsn,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',

    'enableSchemaCache' => true,
    'schemaCacheDuration' => defined('YII_ENV_DEV') && YII_ENV_DEV ? 60 : 3600,
    'schemaCache' => 'cache',
];
