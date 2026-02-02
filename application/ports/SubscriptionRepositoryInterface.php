<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): int;
}
