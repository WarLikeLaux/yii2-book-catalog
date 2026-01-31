<?php

declare(strict_types=1);

use app\presentation\authors\dto\AuthorViewViewModel;
use yii\helpers\Html;

/**
 * @var AuthorViewViewModel $viewModel
 */
$this->title = $viewModel->author->fio;
$this->params['breadcrumbs'][] = ['label' => 'Авторы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="author-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $viewModel->author->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $viewModel->author->id], [
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
            <td><?= Html::encode((string)$viewModel->author->id) ?></td>
        </tr>
        <tr>
            <th>ФИО</th>
            <td><?= Html::encode($viewModel->author->fio) ?></td>
        </tr>
    </table>
</div>