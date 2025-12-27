<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\services\BookSearchPresentationService;
use app\presentation\services\LoginPresentationService;
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
        private readonly BookSearchPresentationService $bookSearchPresentationService,
        private readonly LoginPresentationService $loginPresentationService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    #[\Override]
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

    #[\Override]
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
        $viewData = $this->bookSearchPresentationService->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }

    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        if (!$this->request->isPost) {
            $viewData = $this->loginPresentationService->prepareLoginViewData();
            return $this->render('login', $viewData);
        }

        $result = $this->loginPresentationService->processLoginRequest($this->request);

        if ($result['success']) {
            return $this->goBack();
        }

        return $this->render('login', $result['viewData']);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionApi(): string
    {
        return $this->render('api');
    }
}
