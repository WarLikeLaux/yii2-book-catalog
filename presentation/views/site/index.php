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

    <div id="book-list">
        <?= $this->render('_book-cards', ['dataProvider' => $viewModel->dataProvider]) ?>
    </div>
</div>

<?php
Modal::begin([
    'title' => Yii::t('app', 'ui.subscription_title'),
    'id' => 'sub-modal',
    'size' => Modal::SIZE_DEFAULT,
]);
echo '<div id="modal-content">' . Yii::t('app', 'ui.loading') . '</div>';
Modal::end();
?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container">
    <div id="app-toast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php
$errorSubscription = Yii::t('app', 'ui.error_subscription');
$errorRequest = Yii::t('app', 'ui.error_request');
$subscriptionUrl = Url::to(['/subscription/form']);
$js = <<<JS
function showToast(message, bgClass) {
    var toast = document.getElementById('app-toast');
    var body = document.getElementById('toast-body');
    toast.className = 'toast align-items-center border-0 ' + bgClass;
    body.textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast).show();
}
document.body.addEventListener('htmx:configRequest', function(evt) {
    evt.detail.headers['X-Requested-With'] = 'XMLHttpRequest';
});
document.addEventListener('click', function(e) {
    let link = e.target.closest('.sub-link');
    if (!link) return;
    e.preventDefault();
    let id = link.dataset.id;
    let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('sub-modal'));
    modal.show();
    htmx.ajax('GET', '{$subscriptionUrl}?authorId=' + id, '#modal-content');
});

$(document).on('beforeSubmit', '#subscription-form', function(e) {
    e.preventDefault();
    var form = $(this);
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showToast(data.message, 'text-bg-success');
                $('#sub-modal').modal('hide');
                form[0].reset();
            } else {
                showToast(data.message || '{$errorSubscription}', 'text-bg-danger');
            }
        },
        error: function() {
            showToast('{$errorRequest}', 'text-bg-danger');
        }
    });
    return false;
});

document.body.addEventListener('htmx:afterSwap', function(evt) {
    if (evt.detail.target.id === 'book-list' || evt.detail.target.id === 'book-cards-container') {
        if (typeof GLightbox !== 'undefined') {
            GLightbox({ selector: '.glightbox' });
        }
    }
});
JS;
$this->registerJs($js);
?>
