<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\authors\handlers\AuthorSearchHandler;
use app\presentation\authors\handlers\AuthorViewDataFactory;
use app\presentation\common\filters\IdempotencyFilter;
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
        private readonly AuthorViewDataFactory $viewDataFactory,
        private readonly AuthorSearchHandler $authorSearchHandler,
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
                    [
                        'allow' => true,
                        'actions' => ['search'],
                        'roles' => ['@'],
                    ],
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
        $p = $this->request->get('page', 1);
        $ps = $this->request->get('pageSize', 20);
        $page = max(1, is_numeric($p) ? (int)$p : 1);
        $pageSize = max(1, is_numeric($ps) ? (int)$ps : 20);

        $dataProvider = $this->viewDataFactory->getIndexDataProvider($page, $pageSize);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $viewData = $this->viewDataFactory->getAuthorView($id);
        return $this->render('view', ['model' => $viewData]);
    }

    public function actionCreate(): string|Response
    {
        $form = new AuthorForm();

        if ($this->request->isPost && $form->load((array)$this->request->post()) && $form->validate()) {
            $authorId = $this->commandHandler->createAuthor($form);
            if ($authorId !== null) {
                return $this->redirect(['view', 'id' => $authorId]);
            }
        }

        return $this->render('create', ['model' => $form]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $form = $this->viewDataFactory->getAuthorForUpdate($id);

        if ($this->request->isPost && $form->load((array)$this->request->post()) && $form->validate()) {
            $success = $this->commandHandler->updateAuthor($id, $form);
            if ($success) {
                return $this->redirect(['view', 'id' => $id]);
            }
        }

        return $this->render('update', ['model' => $form]);
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
        return $this->authorSearchHandler->search($this->request, $this->response);
    }
}
