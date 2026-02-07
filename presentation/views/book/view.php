<?php

declare(strict_types=1);

use app\presentation\books\dto\BookViewViewModel;
use app\presentation\books\widgets\BookStatusActions;
use app\presentation\books\widgets\BookStatusBadge;
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
        <?= BookStatusActions::widget(['bookId' => $viewModel->book->id, 'status' => $viewModel->book->status]) ?>
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
            <td><?= BookStatusBadge::widget(['status' => $viewModel->book->status]) ?></td>
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