<?php

declare(strict_types=1);

use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\decorators\AuthorRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\BookRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\IdempotencyRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\RateLimitRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\ReportRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\SubscriptionRepositoryTracingDecorator;
use app\infrastructure\repositories\IdempotencyRepository;
use app\infrastructure\repositories\RateLimitRepository;
use app\infrastructure\repositories\ReportRepository;
use app\infrastructure\repositories\SubscriptionRepository;
use Psr\Log\LoggerInterface;
use yii\di\Container;
use yii\di\Instance;

return static fn (array $params) => [
    IdempotencyRepository::class => [
        'class' => IdempotencyRepository::class,
        '__construct()' => [
            Instance::of(LoggerInterface::class),
        ],
    ],
    IdempotencyInterface::class => static fn(Container $c): IdempotencyInterface => TracingFactory::create(
        $c,
        IdempotencyRepository::class,
        IdempotencyRepositoryTracingDecorator::class
    ),

    RateLimitRepository::class => static fn() => new RateLimitRepository(Yii::$app->get('redis')),
    RateLimitInterface::class => static fn(Container $c): RateLimitInterface => TracingFactory::create(
        $c,
        RateLimitRepository::class,
        RateLimitRepositoryTracingDecorator::class
    ),

    BookRepository::class => static fn() => new BookRepository(Yii::$app->get('db')),
    BookRepositoryInterface::class => static fn(Container $c): BookRepositoryInterface => TracingFactory::create(
        $c,
        BookRepository::class,
        BookRepositoryTracingDecorator::class
    ),

    AuthorRepository::class => static fn() => new AuthorRepository(Yii::$app->get('db')),
    AuthorRepositoryInterface::class => static fn(Container $c): AuthorRepositoryInterface => TracingFactory::create(
        $c,
        AuthorRepository::class,
        AuthorRepositoryTracingDecorator::class
    ),

    SubscriptionRepository::class => static fn() => new SubscriptionRepository(Yii::$app->get('db')),
    SubscriptionRepositoryInterface::class => static fn(Container $c): SubscriptionRepositoryInterface => TracingFactory::create(
        $c,
        SubscriptionRepository::class,
        SubscriptionRepositoryTracingDecorator::class
    ),

    ReportRepository::class => static fn() => new ReportRepository(Yii::$app->get('db')),
    ReportRepositoryInterface::class => static fn(Container $c): ReportRepositoryInterface => TracingFactory::create(
        $c,
        ReportRepository::class,
        ReportRepositoryTracingDecorator::class
    ),
];
