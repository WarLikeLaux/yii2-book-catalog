<?php

declare(strict_types=1);

use app\domain\repositories\AuthorRepositoryInterface;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\repositories\SubscriptionRepositoryInterface;
use app\infrastructure\factories\TracingFactory;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\decorators\AuthorRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\BookRepositoryTracingDecorator;
use app\infrastructure\repositories\decorators\SubscriptionRepositoryTracingDecorator;
use app\infrastructure\repositories\SubscriptionRepository;
use yii\di\Container;

return static fn (array $_params) => [
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
];
