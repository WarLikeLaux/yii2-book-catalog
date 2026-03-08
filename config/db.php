<?php

declare(strict_types=1);

use app\infrastructure\components\AppDbConnection;
use Env\Env;

$driver = Env::get('DB_DRIVER') ?? 'mysql';

[$host, $port] = match ($driver) {
    'pgsql' => [Env::get('PGSQL_DB_HOST') ?? 'pgsql', Env::get('PGSQL_DB_PORT') ?? '5432'],
    default => [Env::get('MYSQL_DB_HOST') ?? 'db', Env::get('MYSQL_DB_PORT') ?? '3306'],
};

$database = Env::get('DB_NAME') ?? 'yii2basic';
$username = Env::get('DB_USER') ?? 'yii2';
$password = Env::get('DB_PASSWORD') ?? 'secret';

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
