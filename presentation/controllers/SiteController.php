<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\common\exceptions\ApplicationException;
use app\application\ports\AuthServiceInterface;
use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewFactory;
use app\presentation\books\handlers\BookSearchViewFactory;
use app\presentation\common\enums\ActionName;
use app\presentation\common\traits\HtmxDetectionTrait;
use app\presentation\common\ViewModelRenderer;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ErrorAction;
use yii\web\Response;

final class SiteController extends BaseController
{
    use HtmxDetectionTrait;

    public function __construct(
        $id,
        $module,
        private readonly AuthServiceInterface $authService,
        private readonly BookSearchViewFactory $bookSearchViewFactory,
        private readonly AuthViewFactory $authViewFactory,
        ViewModelRenderer $renderer,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [ActionName::LOGOUT->value],
                'rules' => [
                    [
                        'actions' => [ActionName::LOGOUT->value],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    ActionName::LOGOUT->value => ['post'],
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
        $viewModel = $this->bookSearchViewFactory->prepareIndexViewModel($this->request);

        if ($this->isHtmxRequest()) {
            return $this->renderer->renderPartial('_book-cards', ['dataProvider' => $viewModel->dataProvider]);
        }

        return $this->renderer->render('index', $viewModel);
    }

    public function actionLogin(): Response|string
    {
        if (!$this->authService->isGuest()) {
            return $this->goHome();
        }

        $form = new LoginForm();

        if (!$this->request->isPost) {
            return $this->renderLoginForm($form);
        }

        /** @var array<string, mixed> $postData */
        $postData = (array) $this->request->post();

        if (!$form->load($postData) || !$form->validate()) {
            $form->password = '';
            return $this->renderLoginForm($form);
        }

        try {
            $this->authService->login($form->username, $form->password, $form->rememberMe);
            return $this->goBack();
        } catch (ApplicationException $e) {
            $this->addFormError($form, $e);
            $form->password = '';
            return $this->renderLoginForm($form);
        }
    }

    public function actionLogout(): Response
    {
        $this->authService->logout();

        return $this->goHome();
    }

    public function actionApi(): string
    {
        $viewModel = $this->authViewFactory->getApiInfoViewModel(
            (int)Yii::$app->params['swaggerPort'],
            (int)Yii::$app->params['appPort'],
            (string)$this->request->serverName,
        );

        return $this->renderer->render('api', $viewModel);
    }

    private function renderLoginForm(LoginForm $form): string
    {
        $viewModel = $this->authViewFactory->getLoginViewModel($form);

        return $this->renderer->render('login', $viewModel);
    }
}
