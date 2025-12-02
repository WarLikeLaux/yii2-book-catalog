<?php

use app\models\Book;
use yii\helpers\Html;
use yii\grid\GridView;

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
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'title',
            'year',
            'isbn',
            [
                'attribute' => 'authors',
                'value' => function (Book $model) {
                    $authors = [];
                    foreach ($model->authors as $author) {
                        $authors[] = $author->fio;
                    }
                    return implode(', ', $authors);
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
    ]) ?>
</div>

