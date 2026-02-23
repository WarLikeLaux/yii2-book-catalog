<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\TracerInterface;
use app\domain\entities\Subscription;
use app\domain\repositories\SubscriptionRepositoryInterface;
use Override;

final readonly class SubscriptionRepositoryTracingDecorator implements SubscriptionRepositoryInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    #[Override]
    public function save(Subscription $subscription): int
    {
        return $this->tracer->trace('SubRepo::' . __FUNCTION__, fn(): int => $this->repository->save($subscription));
    }
}
