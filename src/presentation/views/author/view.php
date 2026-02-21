<?php

declare(strict_types=1);

use app\presentation\authors\dto\AuthorViewViewModel;
use yii\helpers\Html;

/**
 * @var AuthorViewViewModel $viewModel
 */

$this->title = $viewModel->author->fio;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.authors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="author-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(
            Yii::t('app', 'ui.update'),
            [
                'update',
                'id' => $viewModel->author->id,
            ],
            ['class' => 'btn btn-primary'],
        ) ?>
        <?= Html::a(
            Yii::t('app', 'ui.delete'),
            [
                'delete',
                'id' => $viewModel->author->id,
            ],
            [
                'class' => 'btn btn-danger',
                'data' => ['confirm' => Yii::t('app', 'author.confirm.delete'), 'method' => 'post'],
            ],
        ) ?>
    </p>

    <table class="table table-striped table-bordered">
        <tr>
            <th><?= Yii::t('app', 'ui.id') ?></th>
            <td><?= Html::encode((string)$viewModel->author->id) ?></td>
        </tr>
        <tr>
            <th><?= Yii::t('app', 'ui.fio') ?></th>
            <td><?= Html::encode($viewModel->author->fio) ?></td>
        </tr>
    </table>
</div>
