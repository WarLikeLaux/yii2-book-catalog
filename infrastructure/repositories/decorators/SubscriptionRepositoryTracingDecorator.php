<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TracerInterface;
use app\domain\entities\Subscription;

final readonly class SubscriptionRepositoryTracingDecorator implements SubscriptionRepositoryInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    /**
     * Persist the given Subscription using the underlying repository while recording the operation with the tracer.
     *
     * @param \app\domain\entities\Subscription $subscription The subscription entity to persist.
     */
    #[\Override]
    public function save(Subscription $subscription): void
    {
        $this->tracer->trace('SubRepo::' . __FUNCTION__, fn() => $this->repository->save($subscription));
    }
}