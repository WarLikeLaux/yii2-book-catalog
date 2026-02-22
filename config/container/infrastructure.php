<?php

/**
 * NOTE: Разрыв рекурсии достигается через классы-обёртки в infrastructure/components
 * @see docs/DECISIONS.md (см. пункт "5. Автоматическое внедрение зависимостей")
 */

declare(strict_types=1);

use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\AuthorUsageCheckerInterface;
use app\application\ports\BookIsbnCheckerInterface;
use app\application\ports\SubscriptionExistenceCheckerInterface;
use app\infrastructure\components\AppDbConnection;
use app\infrastructure\components\AppMysqlMutex;
use app\infrastructure\components\AppPgsqlMutex;
use app\infrastructure\components\AppRedisConnection;
use app\infrastructure\queue\HandlerAwareQueue;
use app\infrastructure\services\AuthorExistenceChecker;
use app\infrastructure\services\AuthorUsageChecker;
use app\infrastructure\services\BookIsbnChecker;
use app\infrastructure\services\SubscriptionExistenceChecker;
use yii\caching\CacheInterface as YiiCacheInterface;
use yii\caching\DummyCache;
use yii\db\Connection;
use yii\di\Container;
use yii\mutex\Mutex;
use yii\queue\Queue;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection as RedisConnection;

return static fn(array $_params) => [
    AuthorExistenceCheckerInterface::class => AuthorExistenceChecker::class,
    AuthorUsageCheckerInterface::class => AuthorUsageChecker::class,
    BookIsbnCheckerInterface::class => BookIsbnChecker::class,
    SubscriptionExistenceCheckerInterface::class => SubscriptionExistenceChecker::class,

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
