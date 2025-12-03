<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Обновить книгу';
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="book-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'enableAjaxValidation' => true,
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'year')->textInput(['type' => 'number']) ?>
    <?= $form->field($model, 'isbn')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'authorIds')->checkboxList($authors) ?>
    <?= $form->field($model, 'cover')->fileInput() ?>

    <?php if ($book->cover_url): ?>
        <div class="form-group">
            <label>Текущая обложка</label><br>
            <?= Html::img($book->cover_url, ['alt' => $book->title, 'style' => 'max-width: 200px;']) ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>