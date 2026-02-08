<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\handlers;

use app\application\subscriptions\usecases\SubscribeUseCase;
use app\presentation\common\dto\ApiResponse;
use app\presentation\common\services\WebOperationRunner;
use app\presentation\subscriptions\forms\SubscriptionForm;
use app\presentation\subscriptions\mappers\SubscriptionFormMapper;
use Yii;

final readonly class SubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionFormMapper $mapper,
        private SubscribeUseCase $useCase,
        private WebOperationRunner $operationRunner,
    ) {
    }

    public function subscribe(SubscriptionForm $form): ApiResponse
    {
        $command = $this->mapper->toCommand($form);

        return $this->operationRunner->executeForApi(
            $command,
            $this->useCase,
            Yii::t('app', 'subscription.success.subscribed'),
            ['author_id' => $form->authorId],
        );
    }
}
