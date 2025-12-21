<?php

declare(strict_types=1);

/** @var app\application\authors\queries\AuthorReadDto $author */

use yii\helpers\Html;

$this->title = $author->fio;
$this->params['breadcrumbs'][] = ['label' => 'Авторы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="author-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $author->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $author->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить этого автора?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <table class="table table-striped table-bordered">
        <tr>
            <th>ID</th>
            <td><?= Html::encode((string)$author->id) ?></td>
        </tr>
        <tr>
            <th>ФИО</th>
            <td><?= Html::encode($author->fio) ?></td>
        </tr>
    </table>
</div>