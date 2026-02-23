<?php

declare(strict_types=1);

namespace app\application\ports;

interface SubscriptionExistenceCheckerInterface
{
    public function exists(string $phone, int $authorId): bool;
}
