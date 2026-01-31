<?php

declare(strict_types=1);

use app\presentation\books\dto\BookViewViewModel;
use yii\helpers\Html;

/**
 * @var BookViewViewModel $viewModel
 */

$this->title = $viewModel->book->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.books'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'ui.update'), ['update', 'id' => $viewModel->book->id], ['class' => 'btn btn-primary']) ?>
        <?php if (!$viewModel->book->isPublished): ?>
            <?= Html::a(Yii::t('app', 'ui.publish'), ['publish', 'id' => $viewModel->book->id], [
                'class' => 'btn btn-success',
                'data' => [
                    'confirm' => Yii::t('app', 'book.confirm.publish'),
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a(Yii::t('app', 'ui.delete'), ['delete', 'id' => $viewModel->book->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'book.confirm.delete'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <table class="table table-striped table-bordered">
        <tr>
            <th><?= Yii::t('app', 'ui.status') ?></th>
            <td>
                <?php if ($viewModel->book->isPublished): ?>
                    <span class="badge bg-success"><?= Yii::t('app', 'ui.status_published') ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?= Yii::t('app', 'ui.status_draft') ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.title') ?></th>
            <td><?= Html::encode($viewModel->book->title) ?></td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.year') ?></th>
            <td><?= Html::encode($viewModel->book->year) ?></td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.isbn') ?></th>
            <td><?= Html::encode($viewModel->book->isbn) ?></td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.description') ?></th>
            <td><?= Html::encode($viewModel->book->description) ?></td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.authors') ?></th>
            <td><?= Html::encode(implode(', ', $viewModel->book->authorNames)) ?></td>
        </tr>
        <?php if ($viewModel->book->coverUrl): ?>
        <tr>
            <th><?= Yii::t('app', 'ui.cover') ?></th>
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