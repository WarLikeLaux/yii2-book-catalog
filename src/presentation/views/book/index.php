<?php

declare(strict_types=1);

use app\application\books\queries\BookReadDto;
use app\domain\values\BookStatus;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\widgets\BookStatusBadge;
use yii\bootstrap5\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var BookListViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.books');
$this->params['breadcrumbs'][] = $this->title;

$statusOptions = [];
foreach (BookStatus::cases() as $case) {
    $statusOptions[$case->value] = Yii::t('app', 'ui.status_' . $case->value);
}

?>

<div class="book-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'ui.book_create'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'ui.authors'), ['author/index'], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $viewModel->dataProvider,
        'filterModel' => $viewModel->filterModel,
        'pager' => ['class' => LinkPager::class],
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
                'attribute' => 'authorNames',
                'label' => Yii::t('app', 'ui.authors'),
                'filterAttribute' => 'author',
                'value' => static fn (BookReadDto $model): string => implode(', ', $model->authorNames),
            ],
            [
                'attribute' => 'status',
                'label' => Yii::t('app', 'ui.status'),
                'format' => 'raw',
                'filter' => $statusOptions,
                'value' => static fn (BookReadDto $model): string => BookStatusBadge::widget(['status' => $model->status]),
            ],
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]) ?>
</div>
