<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\books\queries\BookQueryService;
use app\models\forms\LoginForm;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\mappers\BookSearchCriteriaMapper;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

final class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookQueryService $bookQueryService,
        private readonly BookSearchCriteriaMapper $bookSearchCriteriaMapper,
        private readonly PagedResultDataProviderFactory $dataProviderFactory,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionIndex(): string
    {
        $form = $this->bookSearchCriteriaMapper->toForm($this->request->get());
        $criteria = $this->bookSearchCriteriaMapper->toCriteria($form);
        $result = $this->bookQueryService->search($criteria);
        $dataProvider = $this->dataProviderFactory->create($result);

        return $this->render('index', [
            'searchModel' => $form,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $form = new LoginForm();
        if ($form->load($this->request->post()) && $form->login()) {
            return $this->goBack();
        }

        $form->password = '';
        return $this->render('login', [
            'model' => $form,
        ]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
