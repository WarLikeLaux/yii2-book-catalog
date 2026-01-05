<?php

/**
 * NOTE: Разрыв рекурсии достигается через классы-обёртки в infrastructure/components
 * @see docs/DECISIONS.md (см. пункт "5. Автоматическое внедрение зависимостей")
 */

declare(strict_types=1);

use app\infrastructure\components\AppDbConnection;
use app\infrastructure\components\AppMysqlMutex;
use app\infrastructure\components\AppPgsqlMutex;
use app\infrastructure\components\AppRedisConnection;
use app\infrastructure\queue\HandlerAwareQueue;
use yii\caching\CacheInterface as YiiCacheInterface;
use yii\caching\DummyCache;
use yii\db\Connection;
use yii\di\Container;
use yii\mutex\Mutex;
use yii\queue\Queue;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection as RedisConnection;

return static fn(array $_params) => [
    Connection::class => static fn() => Yii::$app->get('db'),
    RedisConnection::class => static fn() => Yii::$app->get('redis'),
    Mutex::class => static fn() => Yii::$app->get('mutex'),
    Queue::class => static fn() => Yii::$app->get('queue'),
    YiiCacheInterface::class => static fn() => Yii::$app->get('cache'),
    Container::class => static fn() => Yii::$container,

    AppDbConnection::class => [],
    AppRedisConnection::class => [],
    AppPgsqlMutex::class => [],
    AppMysqlMutex::class => [],
    HandlerAwareQueue::class => [],
    RedisCache::class => [],
    DummyCache::class => [],
];
