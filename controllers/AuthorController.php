<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\services\AuthorService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class AuthorController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly AuthorService $authorService,
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
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Author::find()->orderBy(['fio' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new Author();

        if ($this->request->isPost && $model->load($this->request->post())) {
            try {
                $this->authorService->create($model->fio);
                Yii::$app->session->setFlash('success', 'Автор создан');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id)
    {
        $model = Author::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Автор не найден');
        }

        if ($this->request->isPost && $model->load($this->request->post())) {
            try {
                $this->authorService->update($id, $model->fio);
                Yii::$app->session->setFlash('success', 'Автор обновлен');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id)
    {
        try {
            $this->authorService->delete($id);
            Yii::$app->session->setFlash('success', 'Автор удален');
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }
}

