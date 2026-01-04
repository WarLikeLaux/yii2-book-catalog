<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\handlers\BookViewDataFactory;
use app\presentation\common\dto\CrudPaginationRequest;
use app\presentation\common\filters\IdempotencyFilter;
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
        private readonly BookCommandHandler $commandHandler,
        private readonly BookViewDataFactory $viewDataFactory,
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
                'only' => ['create', 'update'],
            ],
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
                    'publish' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $pagination = CrudPaginationRequest::fromRequest($this->request);

        $dataProvider = $this->viewDataFactory->getIndexDataProvider(
            $pagination->page,
            $pagination->limit
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $book = $this->viewDataFactory->getBookView($id);

        return $this->render('view', [
            'book' => $book,
        ]);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionCreate(): string|Response|array
    {
        $form = new BookForm();

        if ($this->request->isPost && $form->loadFromRequest($this->request)) {
            if ($this->request->isAjax) {
                $this->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($form);
            }

            if ($form->validate()) {
                $bookId = $this->commandHandler->createBook($form);

                if ($bookId !== null) {
                    return $this->redirect(['view', 'id' => $bookId]);
                }
            }
        }

        $authors = $this->viewDataFactory->getAuthorsList();

        return $this->render('create', [
            'model' => $form,
            'authors' => $authors,
        ]);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionUpdate(int $id): string|Response|array
    {
        $form = $this->viewDataFactory->getBookForUpdate($id);

        if ($this->request->isPost && $form->loadFromRequest($this->request)) {
            if ($this->request->isAjax) {
                $this->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($form);
            }

            if ($form->validate()) {
                $success = $this->commandHandler->updateBook($id, $form);

                if ($success) {
                    return $this->redirect(['view', 'id' => $id]);
                }
            }
        }

        $authors = $this->viewDataFactory->getAuthorsList();
        $bookDto = $this->viewDataFactory->getBookView($id);

        return $this->render('update', [
            'model' => $form,
            'authors' => $authors,
            'book' => $bookDto,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->commandHandler->deleteBook($id);
        return $this->redirect(['index']);
    }

    public function actionPublish(int $id): Response
    {
        $this->commandHandler->publishBook($id);
        return $this->redirect(['view', 'id' => $id]);
    }
}
