<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\authors\queries\AuthorQueryService;
use app\application\subscriptions\commands\SubscribeCommand;
use app\application\subscriptions\mappers\SubscriptionFormMapper;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\application\UseCaseExecutor;
use app\models\forms\SubscriptionForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class SubscriptionController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly SubscribeUseCase $subscribeUseCase,
        private readonly AuthorQueryService $authorQueryService,
        private readonly SubscriptionFormMapper $subscriptionFormMapper,
        private readonly UseCaseExecutor $useCaseExecutor,
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

    public function actionSubscribe(): Response
    {
        $this->response->format = Response::FORMAT_JSON;

        $form = new SubscriptionForm();
        if (!$form->load($this->request->post()) || !$form->validate()) {
            return $this->asJson(['success' => false, 'errors' => $form->errors]);
        }

        $command = $this->subscriptionFormMapper->toCommand($form);

        $result = $this->useCaseExecutor->executeForApi(
            fn() => $this->subscribeUseCase->execute($command),
            Yii::t('app', 'You are subscribed!'),
            ['author_id' => $form->authorId]
        );

        return $this->asJson($result);
    }

    public function actionForm(int $authorId): string
    {
        $author = $this->authorQueryService->getById($authorId);
        $form = new SubscriptionForm();

        return $this->renderAjax('_form', [
            'model' => $form,
            'author' => $author,
            'authorId' => $authorId,
        ]);
    }
}
