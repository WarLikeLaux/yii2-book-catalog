<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\filters\IdempotencyFilter;
use app\presentation\forms\SubscriptionForm;
use app\presentation\services\subscriptions\SubscriptionCommandService;
use app\presentation\services\subscriptions\SubscriptionViewService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SubscriptionController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly SubscriptionCommandService $commandService,
        private readonly SubscriptionViewService $viewService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    #[\Override]
    public function behaviors(): array
    {
        return [
            'idempotency' => [
                'class' => IdempotencyFilter::class,
                'only' => ['subscribe'],
            ],
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

    /**
     * @return array<string, mixed>
     */
    public function actionSubscribe(): array
    {
        $this->response->format = Response::FORMAT_JSON;
        $form = new SubscriptionForm();

        if ($form->load((array)$this->request->post()) && $form->validate()) {
            return $this->commandService->subscribe($form);
        }

        return ['success' => false, 'errors' => $form->errors];
    }

    public function actionForm(int $authorId): string
    {
        $author = $this->viewService->getAuthor($authorId);
        $form = new SubscriptionForm();

        return $this->renderAjax('_form', [
            'model' => $form,
            'author' => $author,
            'authorId' => $authorId,
        ]);
    }
}
