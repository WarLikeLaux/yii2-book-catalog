<?php

declare(strict_types=1);

/** @var app\application\books\queries\BookReadDto $book */

use yii\helpers\Html;

$this->title = $book->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $book->id], ['class' => 'btn btn-primary']) ?>
        <?php if (!$book->isPublished): ?>
            <?= Html::a('Опубликовать', ['publish', 'id' => $book->id], [
                'class' => 'btn btn-success',
                'data' => [
                    'confirm' => 'Опубликовать книгу? Подписчики получат уведомления.',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Удалить', ['delete', 'id' => $book->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить эту книгу?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <table class="table table-striped table-bordered">
        <tr>
            <th>Статус</th>
            <td>
                <?php if ($book->isPublished): ?>
                    <span class="badge bg-success">Опубликовано</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Черновик</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Название</th>
            <td><?= Html::encode($book->title) ?></td>
        </tr>
        <tr>
            <th>Год</th>
            <td><?= Html::encode($book->year) ?></td>
        </tr>
        <tr>
            <th>ISBN</th>
            <td><?= Html::encode($book->isbn) ?></td>
        </tr>
        <tr>
            <th>Описание</th>
            <td><?= Html::encode($book->description) ?></td>
        </tr>
        <tr>
            <th>Авторы</th>
            <td><?= Html::encode(implode(', ', $book->authorNames)) ?></td>
        </tr>
        <?php if ($book->coverUrl): ?>
        <tr>
            <th>Обложка</th>
            <td><?= Html::img($book->coverUrl, ['alt' => $book->title, 'style' => 'max-width: 300px;']) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
