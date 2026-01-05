<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$driver = env('DB_DRIVER', 'mysql');

[$host, $port] = match ($driver) {
    'pgsql' => [env('PGSQL_DB_HOST', 'pgsql'), env('PGSQL_DB_PORT', '5432')],
    default => [env('MYSQL_DB_HOST', 'db'), env('MYSQL_DB_PORT', '3306')],
};

$database = env('DB_TEST_NAME', 'yii2basic_test');
$username = env('DB_USER', 'yii2');
$password = env('DB_PASSWORD', 'secret');

return [
    'class' => \app\infrastructure\components\AppDbConnection::class,
    'dsn' => "{$driver}:host={$host};port={$port};dbname={$database}",
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',
];
