<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\authors\handlers\AuthorItemViewFactory;
use app\presentation\authors\handlers\AuthorListViewFactory;
use app\presentation\authors\handlers\AuthorSearchHandler;
use app\presentation\common\dto\CrudPaginationRequest;
use app\presentation\common\filters\IdempotencyFilter;
use Override;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class AuthorController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly AuthorCommandHandler $commandHandler,
        private readonly AuthorListViewFactory $listViewFactory,
        private readonly AuthorItemViewFactory $itemViewFactory,
        private readonly AuthorSearchHandler $authorSearchHandler,
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
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
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
        $pagination = CrudPaginationRequest::fromRequest($this->request);
        $viewModel = $this->listViewFactory->getListViewModel($pagination->page, $pagination->limit);

        return $this->render('index', [
            'viewModel' => $viewModel,
        ]);
    }

    public function actionView(int $id): string
    {
        $viewModel = $this->itemViewFactory->getAuthorViewModel($id);
        return $this->render('view', ['viewModel' => $viewModel]);
    }

    public function actionCreate(): string|Response
    {
        $form = $this->itemViewFactory->createForm();

        if ($this->request->isPost && $form->load((array)$this->request->post()) && $form->validate()) {
            $authorId = $this->commandHandler->createAuthor($form);

            if ($authorId !== null) {
                return $this->redirect(['view', 'id' => $authorId]);
            }
        }

        $viewModel = $this->itemViewFactory->getCreateViewModel($form);

        return $this->render('create', ['viewModel' => $viewModel]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $form = $this->itemViewFactory->getAuthorForUpdate($id);

        if ($this->request->isPost && $form->load((array)$this->request->post()) && $form->validate()) {
            $success = $this->commandHandler->updateAuthor($id, $form);

            if ($success) {
                return $this->redirect(['view', 'id' => $id]);
            }
        }

        $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);

        return $this->render('update', [
            'viewModel' => $viewModel,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $this->commandHandler->deleteAuthor($id);
        return $this->redirect(['index']);
    }

    /**
     * @return array<string, mixed>
     */
    public function actionSearch(): array
    {
        $this->response->format = Response::FORMAT_JSON;
        /** @var array<string, mixed> $params */
        $params = $this->request->get();
        return $this->authorSearchHandler->search($params);
    }
}
