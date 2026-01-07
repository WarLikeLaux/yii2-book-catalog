<?php

declare(strict_types=1);

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\IdempotencyInterface;
use app\application\ports\RateLimitInterface;
use app\application\ports\ReportRepositoryInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\repositories\AsyncIdempotencyRepository;
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

return static fn (array $_params) => [
    AsyncIdempotencyStorageInterface::class => AsyncIdempotencyRepository::class,

    IdempotencyRepository::class => [
        'class' => IdempotencyRepository::class,
        '__construct()' => [
            Instance::of(LoggerInterface::class),
        ],
    ],
    IdempotencyInterface::class => static fn(Container $c): IdempotencyInterface => TracingFactory::create(
        $c,
        IdempotencyRepository::class,
        IdempotencyRepositoryTracingDecorator::class,
    ),

    RateLimitInterface::class => static fn(Container $c): RateLimitInterface => TracingFactory::create(
        $c,
        RateLimitRepository::class,
        RateLimitRepositoryTracingDecorator::class,
    ),

    BookRepositoryInterface::class => static fn(Container $c): BookRepositoryInterface => TracingFactory::create(
        $c,
        BookRepository::class,
        BookRepositoryTracingDecorator::class,
    ),

    AuthorRepositoryInterface::class => static fn(Container $c): AuthorRepositoryInterface => TracingFactory::create(
        $c,
        AuthorRepository::class,
        AuthorRepositoryTracingDecorator::class,
    ),

    SubscriptionRepositoryInterface::class => static fn(Container $c): SubscriptionRepositoryInterface => TracingFactory::create(
        $c,
        SubscriptionRepository::class,
        SubscriptionRepositoryTracingDecorator::class,
    ),

    ReportRepositoryInterface::class => static fn(Container $c): ReportRepositoryInterface => TracingFactory::create(
        $c,
        ReportRepository::class,
        ReportRepositoryTracingDecorator::class,
    ),
];
