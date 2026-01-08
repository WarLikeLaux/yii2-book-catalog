<?php

declare(strict_types=1);

namespace app\application\ports;

use app\domain\entities\Subscription;

interface SubscriptionRepositoryInterface
{
    /**
 * Persist the given Subscription to the repository.
 *
 * @param Subscription $subscription The subscription entity to save.
 */
public function save(Subscription $subscription): void;
}