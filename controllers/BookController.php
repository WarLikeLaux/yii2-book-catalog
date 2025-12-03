<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Book;
use app\models\forms\BookForm;
use app\services\AuthorService;
use app\services\BookService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookService $bookService,
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
            'query' => Book::find()->with('authors')->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $book = Book::find()
            ->where(['id' => $id])
            ->with('authors')
            ->one();

        if (!$book) {
            throw new NotFoundHttpException('Книга не найдена');
        }

        return $this->render('view', ['book' => $book]);
    }

    public function actionCreate()
    {
        $form = new BookForm();

        if ($this->request->isPost) {
            $form->loadFromRequest($this->request);

            if ($this->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return \yii\widgets\ActiveForm::validate($form);
            }

            if ($form->validate()) {
                try {
                    $this->bookService->create($form);
                    Yii::$app->session->setFlash('success', 'Книга создана');
                    return $this->redirect(['index']);
                } catch (\Throwable $e) {
                    Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $form,
            'authors' => $this->authorService->getAuthorsMap(),
        ]);
    }

    public function actionUpdate(int $id)
    {
        $book = Book::findOne($id);
        if (!$book) {
            throw new NotFoundHttpException('Книга не найдена');
        }

        $form = new BookForm();
        $form->id = $book->id;
        $form->title = $book->title;
        $form->year = $book->year;
        $form->description = $book->description;
        $form->isbn = $book->isbn;
        $form->authorIds = $book->getAuthors()->select('id')->column();

        if ($this->request->isPost) {
            $form->loadFromRequest($this->request);

            if ($this->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return \yii\widgets\ActiveForm::validate($form);
            }

            if ($form->validate()) {
                try {
                    $this->bookService->update($id, $form);
                    Yii::$app->session->setFlash('success', 'Книга обновлена');
                    return $this->redirect(['view', 'id' => $id]);
                } catch (\Throwable $e) {
                    Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('update', [
            'model' => $form,
            'book' => $book,
            'authors' => $this->authorService->getAuthorsMap(),
        ]);
    }

    public function actionDelete(int $id)
    {
        try {
            $this->bookService->delete($id);
            Yii::$app->session->setFlash('success', 'Книга удалена');
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }
}
