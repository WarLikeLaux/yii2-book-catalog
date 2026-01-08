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
    /**
     * Create a SubscribeUseCase with its required persistence and query dependencies.
     *
     * @param SubscriptionRepositoryInterface $subscriptionRepository Repository used to persist new subscriptions.
     * @param SubscriptionQueryServiceInterface $subscriptionQueryService Service used to check for existing subscriptions.
     */
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionQueryServiceInterface $subscriptionQueryService,
    ) {
    }

    /**
     * Creates a new subscription based on the provided command.
     *
     * @param SubscribeCommand $command Command containing the phone number and authorId for the subscription.
     * @return bool `true` when the subscription is created successfully.
     * @throws BusinessRuleException If a subscription for the given phone and authorId already exists.
     * @throws AlreadyExistsException If a conflicting subscription already exists while persisting.
     * @throws OperationFailedException If subscription creation or persistence fails for other reasons.
     */
    public function execute(object $command): bool
    {
        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($command instanceof SubscribeCommand);

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