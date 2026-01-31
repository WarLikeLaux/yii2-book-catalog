<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\common\dto\ApiResponse;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\subscriptions\handlers\SubscriptionCommandHandler;
use app\presentation\subscriptions\handlers\SubscriptionViewDataFactory;
use Override;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SubscriptionController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly SubscriptionCommandHandler $commandHandler,
        private readonly SubscriptionViewDataFactory $viewDataFactory,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            'idempotency' => [
                'class' => IdempotencyFilter::class,
                'only' => ['subscribe'],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'subscribe' => ['post'],
                ],
            ],
        ];
    }

    public function actionSubscribe(): Response
    {
        $form = $this->viewDataFactory->createForm();

        if ($this->request->isPost && $form->load((array)$this->request->post()) && $form->validate()) {
            return $this->asJson($this->commandHandler->subscribe($form));
        }

        return $this->asJson(ApiResponse::failure(
            Yii::t('app', 'error.validation'),
            $form->errors,
        ));
    }

    public function actionForm(int $authorId): string
    {
        $viewModel = $this->viewDataFactory->getSubscriptionViewModel($authorId);

        return $this->renderPartial('_form', [
            'viewModel' => $viewModel,
        ]);
    }
}
