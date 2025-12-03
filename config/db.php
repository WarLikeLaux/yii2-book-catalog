<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$host = env('DB_HOST', 'db');
$port = env('DB_PORT', '3306');
$database = env('DB_NAME', 'yii2basic');
$username = env('DB_USER', 'yii2');
$password = env('DB_PASSWORD', 'secret');

return [
    'class' => 'yii\db\Connection',
    'dsn' => "mysql:host={$host};port={$port};dbname={$database}",
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
