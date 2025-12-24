<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\application\common\UseCaseExecutor;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\presentation\forms\SubscriptionForm;
use app\presentation\mappers\SubscriptionFormMapper;
use Yii;
use yii\web\Request;
use yii\web\Response;

final class SubscriptionPresentationService
{
    public function __construct(
        private readonly SubscriptionFormMapper $subscriptionFormMapper,
        private readonly SubscribeUseCase $subscribeUseCase,
        private readonly AuthorQueryService $authorQueryService,
        private readonly UseCaseExecutor $useCaseExecutor
    ) {
    }

    public function processSubscribeRequest(Request $request, Response $response): array
    {
        $response->format = Response::FORMAT_JSON;

        $form = new SubscriptionForm();
        if (!$form->load($request->post()) || !$form->validate()) {
            return ['success' => false, 'errors' => $form->errors];
        }

        $command = $this->subscriptionFormMapper->toCommand($form);

        $result = $this->useCaseExecutor->executeForApi(
            fn() => $this->subscribeUseCase->execute($command),
            Yii::t('app', 'You are subscribed!'),
            ['author_id' => $form->authorId]
        );

        return $result;
    }

    public function prepareFormViewData(int $authorId): array
    {
        $author = $this->authorQueryService->getById($authorId);
        $form = new SubscriptionForm();

        return [
            'model' => $form,
            'author' => $author,
            'authorId' => $authorId,
        ];
    }
}
