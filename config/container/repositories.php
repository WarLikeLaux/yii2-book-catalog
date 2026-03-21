<?php

declare(strict_types=1);

use app\domain\repositories\AuthorRepositoryInterface;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\repositories\SubscriptionRepositoryInterface;
use app\infrastructure\repositories\AuthorRepository;
use app\infrastructure\repositories\BookRepository;
use app\infrastructure\repositories\SubscriptionRepository;

return static fn (array $_params) => [
    BookRepositoryInterface::class => BookRepository::class,

    AuthorRepositoryInterface::class => AuthorRepository::class,

    SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
];
