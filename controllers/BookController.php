<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\queries\BookQueryService;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\models\forms\BookForm;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\mappers\BookFormMapper;
use app\presentation\UseCaseExecutor;
use Yii;
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
        private readonly CreateBookUseCase $createBookUseCase,
        private readonly UpdateBookUseCase $updateBookUseCase,
        private readonly DeleteBookUseCase $deleteBookUseCase,
        private readonly BookFormMapper $bookFormMapper,
        private readonly AuthorQueryService $authorQueryService,
        private readonly BookQueryService $bookQueryService,
        private readonly PagedResultDataProviderFactory $dataProviderFactory,
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
        $queryResult = $this->bookQueryService->getIndexProvider();
        $dataProvider = $this->dataProviderFactory->create($queryResult);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $book = $this->bookQueryService->getById($id);

        return $this->render('view', ['book' => $book]);
    }

    public function actionCreate(): string|Response|array
    {
        $form = new BookForm();

        if (!$this->request->isPost) {
            return $this->render('create', [
                'model' => $form,
                'authors' => $this->authorQueryService->getAuthorsMap(),
            ]);
        }

        $form->loadFromRequest($this->request);

        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }

        if (!$form->validate()) {
            return $this->render('create', [
                'model' => $form,
                'authors' => $this->authorQueryService->getAuthorsMap(),
            ]);
        }

        $command = $this->bookFormMapper->toCreateCommand($form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->createBookUseCase->execute($command),
            Yii::t('app', 'Book has been created')
        );
        if ($success) {
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $form,
            'authors' => $this->authorQueryService->getAuthorsMap(),
        ]);
    }

    public function actionUpdate(int $id): string|Response|array
    {
        $dto = $this->bookQueryService->getById($id);
        $form = $this->bookFormMapper->toForm($dto);

        if (!$this->request->isPost) {
            return $this->render('update', [
                'model' => $form,
                'book' => $dto,
                'authors' => $this->authorQueryService->getAuthorsMap(),
            ]);
        }

        $form->loadFromRequest($this->request);

        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }

        if (!$form->validate()) {
            return $this->render('update', [
                'model' => $form,
                'book' => $dto,
                'authors' => $this->authorQueryService->getAuthorsMap(),
            ]);
        }

        $command = $this->bookFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateBookUseCase->execute($command),
            Yii::t('app', 'Book has been updated'),
            ['book_id' => $id]
        );
        if ($success) {
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('update', [
            'model' => $form,
            'book' => $dto,
            'authors' => $this->authorQueryService->getAuthorsMap(),
        ]);
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
