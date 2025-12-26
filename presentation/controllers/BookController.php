<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\forms\BookForm;
use app\presentation\services\books\BookCommandService;
use app\presentation\services\books\BookViewService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\widgets\ActiveForm;

final class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookCommandService $commandService,
        private readonly BookViewService $viewService,
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $page = max(1, (int)$this->request->get('page', 1));
        $pageSize = max(1, (int)$this->request->get('pageSize', 20));

        $dataProvider = $this->viewService->getIndexDataProvider($page, $pageSize);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $book = $this->viewService->getBookView($id);

        return $this->render('view', [
            'book' => $book,
        ]);
    }

    public function actionCreate(): string|Response|array
    {
        $form = new BookForm();

        if ($this->request->isPost && $form->load($this->request->post())) {
            if ($this->request->isAjax) {
                $this->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($form);
            }

            if ($form->validate()) {
                $bookId = $this->commandService->createBook($form);
                if ($bookId !== null) {
                    return $this->redirect(['view', 'id' => $bookId]);
                }
            }
        }

        $authors = $this->viewService->getAuthorsList();

        return $this->render('create', [
            'model' => $form,
            'authors' => $authors,
        ]);
    }

    public function actionUpdate(int $id): string|Response|array
    {
        $form = $this->viewService->getBookForUpdate($id);

        if ($this->request->isPost && $form->load($this->request->post())) {
            if ($this->request->isAjax) {
                $this->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($form);
            }

            if ($form->validate()) {
                $success = $this->commandService->updateBook($id, $form);
                if ($success) {
                    return $this->redirect(['view', 'id' => $id]);
                }
            }
        }

        $authors = $this->viewService->getAuthorsList();
        $bookDto = $this->viewService->getBookView($id);

        return $this->render('update', [
            'model' => $form,
            'authors' => $authors,
            'book' => $bookDto,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->commandService->deleteBook($id);
        return $this->redirect(['index']);
    }
}
