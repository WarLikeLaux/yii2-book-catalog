<?php

declare(strict_types=1);

use app\application\books\queries\BookReadDto;
use yii\bootstrap5\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать книгу', ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Авторы', ['author/index'], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => [
            'class' => LinkPager::class,
        ],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'title',
            'year',
            'isbn',
            [
                'attribute' => 'authors',
                'value' => static fn (BookReadDto $model) => implode(', ', $model->authorNames),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
    ]) ?>
</div>