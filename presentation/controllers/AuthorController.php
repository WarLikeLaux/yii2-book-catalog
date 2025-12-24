<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\services\AuthorFormPreparationService;
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
        private readonly AuthorFormPreparationService $authorFormPreparationService,
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
        $viewData = $this->authorFormPreparationService->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }

    public function actionView(int $id): string
    {
        $viewData = $this->authorFormPreparationService->prepareViewViewData($id);
        return $this->render('view', $viewData);
    }

    public function actionCreate(): string|Response
    {
        if (!$this->request->isPost) {
            $viewData = $this->authorFormPreparationService->prepareCreateViewData();
            return $this->render('create', $viewData);
        }

        $result = $this->authorFormPreparationService->processCreateRequest($this->request);

        if ($result->success && $result->redirectRoute !== null) {
            return $this->redirect($result->redirectRoute);
        }

        return $this->render('create', $result->viewData);
    }

    public function actionUpdate(int $id): string|Response
    {
        if (!$this->request->isPost) {
            $viewData = $this->authorFormPreparationService->prepareUpdateViewData($id);
            return $this->render('update', $viewData);
        }

        $result = $this->authorFormPreparationService->processUpdateRequest($id, $this->request);

        if ($result->success && $result->redirectRoute !== null) {
            return $this->redirect($result->redirectRoute);
        }

        return $this->render('update', $result->viewData);
    }

    public function actionDelete(int $id): Response
    {
        $this->authorFormPreparationService->processDeleteRequest($id);
        return $this->redirect(['index']);
    }

    public function actionSearch(): array
    {
        return $this->authorSearchPresentationService->search($this->request, $this->response);
    }
}
