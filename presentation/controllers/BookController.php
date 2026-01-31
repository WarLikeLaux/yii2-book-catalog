<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\handlers\BookItemViewFactory;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\common\dto\CrudPaginationRequest;
use app\presentation\common\filters\IdempotencyFilter;
use Override;
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
        private readonly BookListViewFactory $listViewFactory,
        private readonly BookItemViewFactory $itemViewFactory,
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

        $viewModel = $this->listViewFactory->getListViewModel(
            $pagination->page,
            $pagination->limit,
        );

        return $this->render('index', [
            'viewModel' => $viewModel,
        ]);
    }

    public function actionView(int $id): string
    {
        $viewModel = $this->itemViewFactory->getBookViewModel($id);

        return $this->render('view', [
            'viewModel' => $viewModel,
        ]);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionCreate(): string|Response|array
    {
        $form = $this->itemViewFactory->createForm();

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

        $viewModel = $this->itemViewFactory->getCreateViewModel($form);

        return $this->render('create', [
            'viewModel' => $viewModel,
        ]);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionUpdate(int $id): string|Response|array
    {
        $form = $this->itemViewFactory->getBookForUpdate($id);

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

        $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);

        return $this->render('update', [
            'viewModel' => $viewModel,
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
