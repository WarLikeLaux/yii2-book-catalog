<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\forms\AuthorForm;
use app\presentation\services\authors\AuthorCommandService;
use app\presentation\services\authors\AuthorViewService;
use app\presentation\services\AuthorSearchPresentationService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class AuthorController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly AuthorCommandService $commandService,
        private readonly AuthorViewService $viewService,
        private readonly AuthorSearchPresentationService $authorSearchPresentationService,
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

        $dataProvider = $this->viewService->getIndexDataProvider($page, $pageSize);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $viewData = $this->viewService->getAuthorView($id);
        return $this->render('view', ['model' => $viewData]);
    }

    public function actionCreate(): string|Response
    {
        $form = new AuthorForm();

        if ($this->request->isPost && $form->load((array)$this->request->post())) {
            if ($form->validate()) {
                $authorId = $this->commandService->createAuthor($form);
                if ($authorId !== null) {
                    return $this->redirect(['view', 'id' => $authorId]);
                }
            }
        }

        return $this->render('create', ['model' => $form]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $form = $this->viewService->getAuthorForUpdate($id);

        if ($this->request->isPost && $form->load((array)$this->request->post())) {
            if ($form->validate()) {
                $success = $this->commandService->updateAuthor($id, $form);
                if ($success) {
                    return $this->redirect(['view', 'id' => $id]);
                }
            }
        }

        return $this->render('update', ['model' => $form]);
    }

    public function actionDelete(int $id): Response
    {
        $this->commandService->deleteAuthor($id);
        return $this->redirect(['index']);
    }

    /**
     * @return array<string, mixed>
     */
    public function actionSearch(): array
    {
        return $this->authorSearchPresentationService->search($this->request, $this->response);
    }
}
