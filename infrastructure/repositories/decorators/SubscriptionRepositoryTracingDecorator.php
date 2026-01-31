<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\TracerInterface;
use app\domain\entities\Subscription;
use Override;

final readonly class SubscriptionRepositoryTracingDecorator implements SubscriptionRepositoryInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function save(Subscription $subscription): void
    {
        $this->tracer->trace('SubRepo::' . __FUNCTION__, fn() => $this->repository->save($subscription));
    }
}
