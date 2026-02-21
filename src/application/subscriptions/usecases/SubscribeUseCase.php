<?php

declare(strict_types=1);

namespace app\application\subscriptions\usecases;

use app\application\ports\AuthorExistenceCheckerInterface;
use app\application\ports\PhoneNormalizerInterface;
use app\application\ports\SubscriptionExistenceCheckerInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\application\ports\UseCaseInterface;
use app\application\subscriptions\commands\SubscribeCommand;
use app\domain\entities\Subscription;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\values\Phone;

/**
 * @implements UseCaseInterface<SubscribeCommand, bool>
 */
final readonly class SubscribeUseCase implements UseCaseInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
        private SubscriptionExistenceCheckerInterface $subscriptionExistenceChecker,
        private PhoneNormalizerInterface $phoneNormalizer,
    ) {
    }

    /**
     * @param SubscribeCommand $command
     */
    public function execute(object $command): bool
    {
        if (!$this->authorExistenceChecker->existsById($command->authorId)) {
            throw new EntityNotFoundException(DomainErrorCode::SubscriptionInvalidAuthorId);
        }

        $normalized = $this->phoneNormalizer->normalize($command->phone);
        $phone = new Phone($normalized);

        if ($this->subscriptionExistenceChecker->exists($phone->value, $command->authorId)) {
            throw new BusinessRuleException(DomainErrorCode::SubscriptionAlreadySubscribed);
        }

        $subscription = Subscription::create($phone, $command->authorId);
        $this->subscriptionRepository->save($subscription);

        return true;
    }
}
