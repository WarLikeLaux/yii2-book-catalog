<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\queries\AuthorQueryService;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\UseCaseExecutor;
use app\models\forms\AuthorForm;
use app\presentation\mappers\AuthorFormMapper;
use app\presentation\mappers\AuthorSelect2Mapper;
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
        private readonly CreateAuthorUseCase $createAuthorUseCase,
        private readonly UpdateAuthorUseCase $updateAuthorUseCase,
        private readonly DeleteAuthorUseCase $deleteAuthorUseCase,
        private readonly AuthorFormMapper $authorFormMapper,
        private readonly AuthorQueryService $authorQueryService,
        private readonly AuthorSelect2Mapper $authorSelect2Mapper,
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
        $queryResult = $this->authorQueryService->getIndexProvider();
        $dataProvider = $queryResult instanceof \app\application\common\adapters\YiiDataProviderAdapter
            ? $queryResult->toDataProvider()
            : throw new \RuntimeException('Unsupported QueryResultInterface implementation');

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate(): string|Response
    {
        $form = new AuthorForm();

        if (!$this->request->isPost) {
            return $this->render('create', ['model' => $form]);
        }

        if (!$form->load($this->request->post()) || !$form->validate()) {
            return $this->render('create', ['model' => $form]);
        }

        $command = $this->authorFormMapper->toCreateCommand($form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->createAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been created')
        );
        if ($success) {
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $form]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $dto = $this->authorQueryService->getById($id);
        $form = $this->authorFormMapper->toForm($dto);

        if (!$this->request->isPost) {
            return $this->render('update', ['model' => $form]);
        }

        if (!$form->load($this->request->post()) || !$form->validate()) {
            return $this->render('update', ['model' => $form]);
        }

        $command = $this->authorFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been updated'),
            ['author_id' => $id]
        );
        if ($success) {
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $form]);
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
        $this->response->format = Response::FORMAT_JSON;

        $result = $this->authorQueryService->search($this->request->get());

        return $this->authorSelect2Mapper->mapToSelect2($result);
    }
}
