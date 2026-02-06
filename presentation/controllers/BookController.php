<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\handlers\BookItemViewFactory;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\common\enums\ActionName;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\common\ViewModelRenderer;
use Override;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;

final class BookController extends BaseController
{
    public function __construct(
        $id,
        $module,
        private readonly BookCommandHandler $commandHandler,
        private readonly BookListViewFactory $listViewFactory,
        private readonly BookItemViewFactory $itemViewFactory,
        ViewModelRenderer $renderer,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            'idempotency' => [
                'class' => IdempotencyFilter::class,
                'only' => [ActionName::CREATE->value, ActionName::UPDATE->value],
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
                    ActionName::DELETE->value => ['post'],
                    ActionName::PUBLISH->value => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $viewModel = $this->listViewFactory->getListViewModel($this->request);

        return $this->renderer->render('index', $viewModel);
    }

    public function actionView(int $id): string
    {
        $viewModel = $this->itemViewFactory->getBookViewModel($id);

        return $this->renderer->render('view', $viewModel);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionCreate(): string|Response|array
    {
        $form = $this->itemViewFactory->createForm();

        if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
            $viewModel = $this->itemViewFactory->getCreateViewModel($form);
            return $this->renderer->render('create', $viewModel);
        }

        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }

        if (!$form->validate()) {
            $viewModel = $this->itemViewFactory->getCreateViewModel($form);
            return $this->renderer->render('create', $viewModel);
        }

        $bookId = $this->commandHandler->createBook($form);

        if ($bookId === null) {
            $viewModel = $this->itemViewFactory->getCreateViewModel($form);
            return $this->renderer->render('create', $viewModel);
        }

        return $this->redirect(['view', 'id' => $bookId]);
    }

    /**
     * @return string|Response|array<string, mixed>
     */
    public function actionUpdate(int $id): string|Response|array
    {
        $form = $this->itemViewFactory->getBookForUpdate($id);

        if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
            $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);
            return $this->renderer->render('update', $viewModel);
        }

        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }

        if (!$form->validate()) {
            $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);
            return $this->renderer->render('update', $viewModel);
        }

        $success = $this->commandHandler->updateBook($id, $form);

        if (!$success) {
            $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);
            return $this->renderer->render('update', $viewModel);
        }

        return $this->redirect(['view', 'id' => $id]);
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
