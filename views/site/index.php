<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\LinkPager;
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Каталог книг';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-transparent py-4">
        <h1 class="display-5">Библиотека</h1>
        <?php if (!Yii::$app->user->isGuest): ?>
            <p><?= Html::a('Управление книгами', ['book/index'], ['class' => 'btn btn-success']) ?></p>
        <?php endif; ?>
    </div>

    <?php Pjax::begin(['id' => 'book-list-pjax']); ?>
    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'action' => ['site/index'],
        'options' => ['data-pjax' => true],
    ]); ?>

    <div class="row mb-4">
        <div class="col-md-8 offset-md-2">
            <?= $form->field($searchModel, 'globalSearch')
                ->textInput([
                    'placeholder' => 'Название, ISBN, Автор или Год...',
                    'id' => 'book-search-input',
                ])
                ->label(false) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="row">
        <?php foreach ($dataProvider->getModels() as $book): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($book->coverUrl): ?>
                        <div style="height: 200px; overflow: hidden;">
                            <?= Html::img($book->coverUrl, ['class' => 'card-img-top', 'alt' => $book->title, 'style' => 'width: 100%; height: 100%; object-fit: cover;']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= Html::encode($book->title) ?></h5>
                        <p class="text-muted mb-1"><?= Html::encode($book->year) ?></p>

                        <div class="mb-3">
                            <?php foreach ($book->authorNames as $authorId => $fio): ?>
                                <span class="badge bg-secondary me-1">
                                    <?= Html::encode($fio) ?>
                                    <a href="#" class="text-white sub-link" data-id="<?= $authorId ?>" title="Подписаться">
                                        <i class="bi bi-bell"></i> +
                                    </a>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($book->description): ?>
                            <p class="small"><?= Html::encode(mb_substr($book->description, 0, 100)) ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'pagination'],
            'linkOptions' => ['class' => 'page-link'],
            'pageCssClass' => 'page-item',
            'disabledPageCssClass' => 'page-item disabled',
            'prevPageCssClass' => 'page-item',
            'nextPageCssClass' => 'page-item',
        ]) ?>
    </div>
    <?php Pjax::end(); ?>
</div>

<?php
Modal::begin([
    'title' => 'Подписка на автора',
    'id' => 'sub-modal',
    'size' => Modal::SIZE_DEFAULT,
]);
echo '<div id="modal-content">Загрузка...</div>';
Modal::end();

$js = <<<JS
let searchTimeout;
let cursorPosition = 0;

$(document).on('input', '#book-search-input', function() {
    cursorPosition = this.selectionStart;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        $('#book-list-pjax form').submit();
    }, 300);
});

$(document).on('pjax:complete', '#book-list-pjax', function() {
    let input = $('#book-search-input');
    if (input.length) {
        input.focus();
        if (cursorPosition > 0 && cursorPosition <= input.val().length) {
            input[0].setSelectionRange(cursorPosition, cursorPosition);
        }
    }
});

$(document).on('click', '.sub-link', function(e) {
    e.preventDefault();
    let id = $(this).data('id');
    $('#sub-modal').modal('show');
    $('#modal-content').load('/subscription/form?authorId=' + id);
});
JS;
$this->registerJs($js);
?>