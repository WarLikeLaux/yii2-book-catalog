<?php

declare(strict_types=1);

use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\books\widgets\BookStatusBadge;
use yii\bootstrap5\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var BookListViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.books');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'ui.book_create'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'ui.authors'), ['author/index'], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $viewModel->dataProvider,
        'pager' => [
            'class' => LinkPager::class,
        ],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'title',
                'label' => Yii::t('app', 'ui.title'),
            ],
            [
                'attribute' => 'year',
                'label' => Yii::t('app', 'ui.year'),
            ],
            'isbn',
            [
                'attribute' => 'authors',
                'label' => Yii::t('app', 'ui.authors'),
                'value' => static fn (BookViewModel $model): string => implode(', ', $model->authorNames),
            ],
            [
                'attribute' => 'status',
                'label' => Yii::t('app', 'ui.status'),
                'format' => 'raw',
                'value' => static fn (BookViewModel $model): string => BookStatusBadge::widget(['status' => $model->status]),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
    ]) ?>
</div>