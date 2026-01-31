<?php

declare(strict_types=1);

use app\presentation\books\dto\BookEditViewModel;
use yii\helpers\Html;

/** @var BookEditViewModel $viewModel */

$this->title = 'Создать книгу';
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $viewModel->form,
        'authors' => $viewModel->authors,
    ]) ?>
</div>
