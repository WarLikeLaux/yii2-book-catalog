<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\common\exceptions\ApplicationException;
use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\authors\handlers\AuthorItemViewFactory;
use app\presentation\authors\handlers\AuthorListViewFactory;
use app\presentation\authors\handlers\AuthorSearchViewFactory;
use app\presentation\common\enums\ActionName;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\common\ViewModelRenderer;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

final class AuthorController extends BaseController
{
    public function __construct(
        $id,
        $module,
        private readonly AuthorCommandHandler $commandHandler,
        private readonly AuthorListViewFactory $listViewFactory,
        private readonly AuthorItemViewFactory $itemViewFactory,
        private readonly AuthorSearchViewFactory $authorSearchViewFactory,
        ViewModelRenderer $renderer,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            'idempotency' => [
                'class' => IdempotencyFilter::class,
                'only' => [ActionName::CREATE->value, ActionName::UPDATE->value],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    ActionName::DELETE->value => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $viewModel = $this->listViewFactory->getListViewModel($this->request);

        return $this->renderer->render('index', $viewModel);
    }

    public function actionView(int $id): string
    {
        $viewModel = $this->itemViewFactory->getAuthorViewModel($id);
        return $this->renderer->render('view', $viewModel);
    }

    public function actionCreate(): string|Response
    {
        $form = $this->itemViewFactory->createForm();

        if ($this->request->isPost && $form->loadFromRequest($this->request) && $form->validate()) {
            try {
                $authorId = $this->commandHandler->createAuthor($form);
                return $this->redirect(['view', 'id' => $authorId]);
            } catch (ApplicationException $e) {
                $this->addFormError($form, $e);
            }
        }

        $viewModel = $this->itemViewFactory->getCreateViewModel($form);

        return $this->renderer->render('create', $viewModel);
    }

    public function actionUpdate(int $id): string|Response
    {
        $form = $this->itemViewFactory->getAuthorForUpdate($id);

        if ($this->request->isPost && $form->loadFromRequest($this->request) && $form->validate()) {
            try {
                $this->commandHandler->updateAuthor($id, $form);
                return $this->redirect(['view', 'id' => $id]);
            } catch (ApplicationException $e) {
                $this->addFormError($form, $e);
            }
        }

        $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);

        return $this->renderer->render('update', $viewModel);
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->commandHandler->deleteAuthor($id);
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['index']);
    }

    public function actionSearch(): Response
    {
        /** @var array<string, mixed> $params */
        $params = $this->request->get();
        return $this->asJson($this->authorSearchViewFactory->search($params));
    }
}
