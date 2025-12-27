<?php

declare(strict_types=1);

namespace app\presentation\services\subscriptions;

use app\application\common\UseCaseExecutor;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\presentation\forms\SubscriptionForm;
use app\presentation\mappers\SubscriptionFormMapper;
use Yii;

final class SubscriptionCommandService
{
    public function __construct(
        private readonly SubscriptionFormMapper $mapper,
        private readonly SubscribeUseCase $useCase,
        private readonly UseCaseExecutor $useCaseExecutor
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function subscribe(SubscriptionForm $form): array
    {
        $command = $this->mapper->toCommand($form);

        return $this->useCaseExecutor->executeForApi(
            fn() => $this->useCase->execute($command),
            Yii::t('app', 'You are subscribed!'),
            ['author_id' => $form->authorId]
        );
    }
}
