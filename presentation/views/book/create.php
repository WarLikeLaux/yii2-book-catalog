<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Создать книгу';
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'authors' => $authors ?? [],
    ]) ?>
</div>
