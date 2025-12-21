<?php

declare(strict_types=1);

namespace app\controllers;

use app\presentation\services\SubscriptionPresentationService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

final class SubscriptionController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly SubscriptionPresentationService $subscriptionPresentationService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['form', 'subscribe'],
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'subscribe' => ['post'],
                ],
            ],
        ];
    }

    public function actionSubscribe(): array
    {
        return $this->subscriptionPresentationService->processSubscribeRequest($this->request, $this->response);
    }

    public function actionForm(int $authorId): string
    {
        $viewData = $this->subscriptionPresentationService->prepareFormViewData($authorId);
        return $this->renderAjax('_form', $viewData);
    }
}
