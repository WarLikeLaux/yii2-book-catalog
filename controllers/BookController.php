<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\DeleteBookUseCase;
use app\presentation\services\BookFormPreparationService;
use app\presentation\UseCaseExecutor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly DeleteBookUseCase $deleteBookUseCase,
        private readonly BookFormPreparationService $bookFormPreparationService,
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
        $viewData = $this->bookFormPreparationService->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }

    public function actionView(int $id): string
    {
        $viewData = $this->bookFormPreparationService->prepareViewViewData($id);
        return $this->render('view', $viewData);
    }

    public function actionCreate(): string|Response|array
    {
        if (!$this->request->isPost) {
            $viewData = $this->bookFormPreparationService->prepareCreateViewData();
            return $this->render('create', $viewData);
        }

        $result = $this->bookFormPreparationService->processCreateRequest($this->request, $this->response);

        if ($result->ajaxValidation !== null) {
            return $result->ajaxValidation;
        }

        if ($result->success && $result->redirectRoute !== null) {
            return $this->redirect($result->redirectRoute);
        }

        return $this->render('create', $result->viewData);
    }

    public function actionUpdate(int $id): string|Response|array
    {
        if (!$this->request->isPost) {
            $viewData = $this->bookFormPreparationService->prepareUpdateViewData($id);
            return $this->render('update', $viewData);
        }

        $result = $this->bookFormPreparationService->processUpdateRequest($id, $this->request, $this->response);

        if ($result->ajaxValidation !== null) {
            return $result->ajaxValidation;
        }

        if ($result->success && $result->redirectRoute !== null) {
            return $this->redirect($result->redirectRoute);
        }

        return $this->render('update', $result->viewData);
    }

    public function actionDelete(int $id): Response
    {
        $command = new DeleteBookCommand($id);
        $this->useCaseExecutor->execute(
            fn() => $this->deleteBookUseCase->execute($command),
            Yii::t('app', 'Book has been deleted'),
            ['book_id' => $id]
        );

        return $this->redirect(['index']);
    }
}
