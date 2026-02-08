<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\common\exceptions\ApplicationException;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookCommandHandler;
use app\presentation\books\handlers\BookItemViewFactory;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\common\enums\ActionName;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\common\ViewModelRenderer;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;

final class BookController extends BaseController
{
    public function __construct(
        $id,
        $module,
        private readonly BookCommandHandler $commandHandler,
        private readonly BookListViewFactory $listViewFactory,
        private readonly BookItemViewFactory $itemViewFactory,
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
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    ActionName::DELETE->value => ['post'],
                    ActionName::PUBLISH->value => ['post'],
                    ActionName::UNPUBLISH->value => ['post'],
                    ActionName::ARCHIVE->value => ['post'],
                    ActionName::RESTORE->value => ['post'],
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
        $viewModel = $this->itemViewFactory->getBookViewModel($id);

        return $this->renderer->render('view', $viewModel);
    }

    public function actionCreate(): string|Response
    {
        $form = $this->itemViewFactory->createForm();

        if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
            return $this->renderCreateForm($form);
        }

        if ($this->request->isAjax) {
            return $this->asJson(ActiveForm::validate($form));
        }

        if (!$form->validate()) {
            return $this->renderCreateForm($form);
        }

        try {
            $bookId = $this->commandHandler->createBook($form);
            return $this->redirect(['view', 'id' => $bookId]);
        } catch (ApplicationException $e) {
            $this->addFormError($form, $e);
            return $this->renderCreateForm($form);
        }
    }

    public function actionUpdate(int $id): string|Response
    {
        $form = $this->itemViewFactory->getBookForUpdate($id);

        if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
            return $this->renderUpdateForm($id, $form);
        }

        if ($this->request->isAjax) {
            return $this->asJson(ActiveForm::validate($form));
        }

        if (!$form->validate()) {
            return $this->renderUpdateForm($id, $form);
        }

        try {
            $this->commandHandler->updateBook($id, $form);
            return $this->redirect(['view', 'id' => $id]);
        } catch (ApplicationException $e) {
            $this->addFormError($form, $e);
            return $this->renderUpdateForm($id, $form);
        }
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->commandHandler->deleteBook($id);
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['index']);
    }

    public function actionPublish(int $id): Response
    {
        try {
            $this->commandHandler->changeBookStatus($id, 'published', Yii::t('app', 'book.success.published'));
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionUnpublish(int $id): Response
    {
        try {
            $this->commandHandler->changeBookStatus($id, 'draft', Yii::t('app', 'book.success.unpublished'));
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionArchive(int $id): Response
    {
        try {
            $this->commandHandler->changeBookStatus($id, 'archived', Yii::t('app', 'book.success.archived'));
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionRestore(int $id): Response
    {
        try {
            $this->commandHandler->changeBookStatus($id, 'draft', Yii::t('app', 'book.success.restored'));
        } catch (ApplicationException $e) {
            $this->flash('error', Yii::t('app', $e->errorCode));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function renderCreateForm(BookForm $form): string
    {
        $viewModel = $this->itemViewFactory->getCreateViewModel($form);

        return $this->renderer->render('create', $viewModel);
    }

    private function renderUpdateForm(int $id, BookForm $form): string
    {
        $viewModel = $this->itemViewFactory->getUpdateViewModel($id, $form);

        return $this->renderer->render('update', $viewModel);
    }
}
