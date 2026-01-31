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

$this->title = 'Каталог книг';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-transparent py-4">
        <h1 class="display-5">Библиотека</h1>
        <?php if (!Yii::$app->user->isGuest): ?>
            <p><?= Html::a('Управление книгами', ['book/index'], ['class' => 'btn btn-success']) ?></p>
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
                    'placeholder' => 'Название, ISBN, Автор или Год...',
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
    'title' => 'Подписка на автора',
    'id' => 'sub-modal',
    'size' => Modal::SIZE_DEFAULT,
]);
echo '<div id="modal-content">Загрузка...</div>';
Modal::end();

$subscriptionUrl = Url::to(['/subscription/form']);
$js = <<<JS
if (typeof htmx !== 'undefined' && htmx.config) {
    htmx.config.headers = htmx.config.headers || {};
    htmx.config.headers['X-Requested-With'] = 'XMLHttpRequest';
}
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
        success: function(data) {
            if (data.success) {
                alert(data.message);
                $('#sub-modal').modal('hide');
                form[0].reset();
            } else {
                alert(data.message || 'Ошибка при подписке');
            }
        },
        error: function() {
            alert('Ошибка при отправке запроса');
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
