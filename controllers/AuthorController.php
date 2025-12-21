<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\queries\AuthorQueryService;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\presentation\services\AuthorFormPreparationService;
use app\presentation\services\AuthorSearchPresentationService;
use app\presentation\UseCaseExecutor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class AuthorController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly DeleteAuthorUseCase $deleteAuthorUseCase,
        private readonly AuthorFormPreparationService $authorFormPreparationService,
        private readonly AuthorQueryService $authorQueryService,
        private readonly AuthorSearchPresentationService $authorSearchPresentationService,
        private readonly UseCaseExecutor $useCaseExecutor,
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
        $author = $this->authorQueryService->getById($id);

        return $this->render('view', ['author' => $author]);
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

        return $this->render('create', ['model' => $result->form]);
    }

    public function actionUpdate(int $id): string|Response
    {
        if (!$this->request->isPost) {
            $form = $this->authorFormPreparationService->prepareUpdateForm($id);
            return $this->render('update', ['model' => $form]);
        }

        $result = $this->authorFormPreparationService->processUpdateRequest($id, $this->request);

        if ($result->success && $result->redirectRoute !== null) {
            return $this->redirect($result->redirectRoute);
        }

        return $this->render('update', ['model' => $result->form]);
    }

    public function actionDelete(int $id): Response
    {
        $command = new DeleteAuthorCommand($id);
        $this->useCaseExecutor->execute(
            fn() => $this->deleteAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been deleted'),
            ['author_id' => $id]
        );

        return $this->redirect(['index']);
    }

    public function actionSearch(): array
    {
        return $this->authorSearchPresentationService->search($this->request, $this->response);
    }
}
