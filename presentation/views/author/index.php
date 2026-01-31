<?php

declare(strict_types=1);

use app\presentation\authors\dto\AuthorListViewModel;
use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var AuthorListViewModel $viewModel
 */

$this->title = 'Авторы';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="author-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'ui.author_create'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'ui.books'), ['book/index'], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $viewModel->dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'fio',
                'label' => Yii::t('app', 'ui.fio'),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
            ],
        ],
    ]) ?>
</div>

