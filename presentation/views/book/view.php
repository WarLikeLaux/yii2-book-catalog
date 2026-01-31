<?php

declare(strict_types=1);

use app\presentation\books\dto\BookViewViewModel;
use yii\helpers\Html;

/** @var BookViewViewModel $viewModel */

$this->title = $viewModel->book->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $viewModel->book->id], ['class' => 'btn btn-primary']) ?>
        <?php if (!$viewModel->book->isPublished): ?>
            <?= Html::a('Опубликовать', ['publish', 'id' => $viewModel->book->id], [
                'class' => 'btn btn-success',
                'data' => [
                    'confirm' => 'Опубликовать книгу? Подписчики получат уведомления.',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('Удалить', ['delete', 'id' => $viewModel->book->id], [
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
                <?php if ($viewModel->book->isPublished): ?>
                    <span class="badge bg-success">Опубликовано</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Черновик</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Название</th>
            <td><?= Html::encode($viewModel->book->title) ?></td>
        </tr>
        <tr>
            <th>Год</th>
            <td><?= Html::encode($viewModel->book->year) ?></td>
        </tr>
        <tr>
            <th>ISBN</th>
            <td><?= Html::encode($viewModel->book->isbn) ?></td>
        </tr>
        <tr>
            <th>Описание</th>
            <td><?= Html::encode($viewModel->book->description) ?></td>
        </tr>
        <tr>
            <th>Авторы</th>
            <td><?= Html::encode(implode(', ', $viewModel->book->authorNames)) ?></td>
        </tr>
        <?php if ($viewModel->book->coverUrl): ?>
        <tr>
            <th>Обложка</th>
            <td>
                <?= Html::a(
                    Html::img($viewModel->book->coverUrl, ['alt' => $viewModel->book->title, 'style' => 'max-width: 300px; cursor: pointer;', 'loading' => 'lazy']),
                    $viewModel->book->coverUrl,
                    ['class' => 'glightbox', 'data-gallery' => 'book-gallery', 'data-type' => 'image'],
                ) ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>
</div>