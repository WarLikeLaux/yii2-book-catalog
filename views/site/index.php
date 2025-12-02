<?php

declare(strict_types=1);

use yii\bootstrap5\LinkPager;
use yii\bootstrap5\Modal;
use yii\helpers\Html;

$this->title = 'Каталог книг';
?>

<div class="site-index">
    <div class="jumbotron text-center bg-transparent py-4">
        <h1 class="display-5">Библиотека</h1>
        <?php if (!Yii::$app->user->isGuest): ?>
            <p><?= Html::a('Управление книгами', ['book/index'], ['class' => 'btn btn-success']) ?></p>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php foreach ($dataProvider->getModels() as $book): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($book->cover_url): ?>
                        <div style="height: 200px; overflow: hidden;">
                            <?= Html::img($book->cover_url, ['class' => 'card-img-top', 'alt' => $book->title, 'style' => 'width: 100%; height: 100%; object-fit: cover;']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= Html::encode($book->title) ?></h5>
                        <p class="text-muted mb-1"><?= Html::encode($book->year) ?></p>

                        <div class="mb-3">
                            <?php foreach ($book->authors as $author): ?>
                                <span class="badge bg-secondary me-1">
                                    <?= Html::encode($author->fio) ?>
                                    <a href="#" class="text-white sub-link" data-id="<?= $author->id ?>" title="Подписаться">
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
$(document).on('click', '.sub-link', function(e) {
    e.preventDefault();
    let id = $(this).data('id');
    $('#sub-modal').modal('show');
    $('#modal-content').load('/subscription/form?authorId=' + id);
});
JS;
$this->registerJs($js);
?>