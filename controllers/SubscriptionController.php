<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\forms\SubscriptionForm;
use app\services\SubscriptionService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class SubscriptionController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly SubscriptionService $service,
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
        ];
    }

    public function actionSubscribe(): Response
    {
        $this->response->format = Response::FORMAT_JSON;

        $form = new SubscriptionForm();
        if ($form->load($this->request->post()) && $form->validate()) {
            try {
                $this->service->subscribe($form);
                return $this->asJson(['success' => true, 'message' => 'Вы подписаны!']);
            } catch (\Throwable $e) {
                return $this->asJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->asJson(['success' => false, 'errors' => $form->errors]);
    }

    public function actionForm(int $authorId): string
    {
        $author = Author::findOne($authorId);
        if (!$author) {
            throw new NotFoundHttpException('Автор не найден');
        }

        $form = new SubscriptionForm();
        $form->authorId = $authorId;

        return $this->renderAjax('_form', [
            'model' => $form,
            'author' => $author,
        ]);
    }
}
