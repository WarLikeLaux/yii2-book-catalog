<?php

declare(strict_types=1);

use app\presentation\books\dto\BookIndexViewModel;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var BookIndexViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.book_catalog');

?>

<div class="site-index">
    <div class="jumbotron text-center bg-transparent py-4">
        <h1 class="display-5"><?= Yii::t('app', 'ui.library') ?></h1>
        <?php if (!Yii::$app->user->isGuest): ?>
            <p><?= Html::a(Yii::t('app', 'ui.manage_books'), ['book/index'], ['class' => 'btn btn-success']) ?></p>
        <?php endif; ?>
    </div>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'action' => ['site/index'],
        'options' => [
            'id' => 'book-search-form',
            'hx-get' => Url::to(['site/index']),
            'hx-target' => '#book-list',
            'hx-trigger' => 'submit',
            'hx-push-url' => 'true',
        ],
    ]); ?>
        <div class="row mb-4">
            <div class="col-md-8 offset-md-2">
                <?= $form->field($viewModel->searchModel, 'globalSearch')
                    ->textInput([
                        'placeholder' => Yii::t('app', 'ui.search_placeholder'),
                        'id' => 'book-search-input',
                        'hx-get' => Url::to(['site/index']),
                        'hx-target' => '#book-list',
                        'hx-trigger' => 'input changed delay:300ms',
                        'hx-push-url' => 'true',
                        'hx-include' => '#book-search-form',
                    ])
                    ->label(false) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>

    <div id="book-list"><?= $this->render('_book-cards', ['dataProvider' => $viewModel->dataProvider]) ?></div>
</div>

<?php Modal::begin([
    'title' => Yii::t('app', 'ui.subscription_title'),
    'id' => 'sub-modal',
    'size' => Modal::SIZE_DEFAULT,
]);
echo '<div id="modal-content">' . Yii::t('app', 'ui.loading') . '</div>';
Modal::end(); ?>

<div
    class="toast-container position-fixed bottom-0 end-0 p-3"
    id="toast-container"
    data-subscription-url="<?= Url::to(['/subscription/form']) ?>"
    data-error-subscription="<?= Html::encode(Yii::t('app', 'ui.error_subscription')) ?>"
    data-error-request="<?= Html::encode(Yii::t('app', 'ui.error_request')) ?>"
>
    <div id="app-toast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button
                type="button"
                class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast"
                aria-label="Close"
            >
            </button>
        </div>
    </div>
</div>
