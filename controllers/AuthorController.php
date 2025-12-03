<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\forms\AuthorForm;
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
        $form = new AuthorForm();

        if ($this->request->isPost && $form->load($this->request->post()) && $form->validate()) {
            try {
                $this->authorService->create($form->fio);
                Yii::$app->session->setFlash('success', 'Автор создан');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', ['model' => $form]);
    }

    public function actionUpdate(int $id)
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new NotFoundHttpException('Автор не найден');
        }

        $form = new AuthorForm();
        $form->id = $author->id;
        $form->fio = $author->fio;

        if ($this->request->isPost && $form->load($this->request->post()) && $form->validate()) {
            try {
                $this->authorService->update($id, $form->fio);
                Yii::$app->session->setFlash('success', 'Автор обновлен');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', ['model' => $form]);
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
