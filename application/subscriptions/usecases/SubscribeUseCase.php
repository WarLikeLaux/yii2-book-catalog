<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\entities\Subscription;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use Throwable;

/**
 * @implements UseCaseInterface<SubscribeCommand, bool>
 */
final readonly class SubscribeUseCase implements UseCaseInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionQueryServiceInterface $subscriptionQueryService,
    ) {
    }

    /**
     * @param SubscribeCommand $command
     */
    public function execute(object $command): bool
    {
        if ($this->subscriptionQueryService->exists($command->phone, $command->authorId)) {
            throw new BusinessRuleException(DomainErrorCode::SubscriptionAlreadySubscribed);
        }

        try {
            $subscription = Subscription::create($command->phone, $command->authorId);
            $this->subscriptionRepository->save($subscription);

            return true;
        } catch (AlreadyExistsException $e) {
            throw $e;
        } catch (Throwable) {
            throw new OperationFailedException(DomainErrorCode::SubscriptionCreateFailed);
        }
    }
}
