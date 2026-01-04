<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\common\dto\PaginationRequest;
use app\application\ports\AuthServiceInterface;
use app\presentation\auth\handlers\LoginHandler;
use app\presentation\books\handlers\BookSearchHandler;
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
        private readonly AuthServiceInterface $authService,
        private readonly BookSearchHandler $bookSearchHandler,
        private readonly LoginHandler $loginHandler,
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
        $pagination = new PaginationRequest(
            $this->request->get('page'),
            $this->request->get('pageSize') ?? 9
        );
        /** @var array<string, mixed> $params */
        $params = (array)$this->request->get();
        $viewData = $this->bookSearchHandler->prepareIndexViewData($params, $pagination);
        return $this->render('index', $viewData);
    }

    public function actionLogin(): Response|string
    {
        if (!$this->authService->isGuest()) {
            return $this->goHome();
        }

        if (!$this->request->isPost) {
            $viewData = $this->loginHandler->prepareLoginViewData();
            return $this->render('login', $viewData);
        }

        $result = $this->loginHandler->processLoginRequest($this->request);

        if ($result['success']) {
            return $this->goBack();
        }

        return $this->render('login', $result['viewData']);
    }

    public function actionLogout(): Response
    {
        $this->authService->logout();

        return $this->goHome();
    }

    public function actionApi(): string
    {
        return $this->render('api', [
            'swaggerPort' => Yii::$app->params['swaggerPort'],
            'appPort' => Yii::$app->params['appPort'],
            'host' => $this->request->serverName,
        ]);
    }
}
