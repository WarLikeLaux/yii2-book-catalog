<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\ports\AuthServiceInterface;
use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewDataFactory;
use app\presentation\books\handlers\BookSearchHandler;
use app\presentation\common\dto\CatalogPaginationRequest;
use app\presentation\common\traits\HtmxDetectionTrait;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

final class SiteController extends Controller
{
    use HtmxDetectionTrait;

    public function __construct(
        $id,
        $module,
        private readonly AuthServiceInterface $authService,
        private readonly BookSearchHandler $bookSearchHandler,
        private readonly AuthViewDataFactory $authViewDataFactory,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    #[Override]
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

    #[Override]
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
        $pagination = CatalogPaginationRequest::fromRequest($this->request);
        /** @var array<string, mixed> $params */
        $params = (array)$this->request->get();
        $viewModel = $this->bookSearchHandler->prepareIndexViewModel($params, $pagination);

        if ($this->isHtmxRequest()) {
            return $this->renderPartial('_book-cards', ['dataProvider' => $viewModel->dataProvider]);
        }

        return $this->render('index', ['viewModel' => $viewModel]);
    }

    public function actionLogin(): Response|string
    {
        if (!$this->authService->isGuest()) {
            return $this->goHome();
        }

        $form = new LoginForm();

        if ($this->request->isPost) {
            /** @var array<string, mixed> $postData */
            $postData = (array) $this->request->post();

            if ($form->load($postData) && $form->validate()) {
                if ($this->authService->login($form->username, $form->password, $form->rememberMe)) {
                    return $this->goBack();
                }

                $form->addError('password', Yii::t('app', 'auth.error.invalid_credentials'));
                $form->password = '';
            } else {
                $form->password = '';
            }
        }

        $viewModel = $this->authViewDataFactory->getLoginViewModel($form);

        return $this->render('login', ['viewModel' => $viewModel]);
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
