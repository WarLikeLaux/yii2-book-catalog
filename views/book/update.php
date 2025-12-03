<?php

use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

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
    <?= $form->field($model, 'authorIds')->widget(Select2::class, [
        'initValueText' => array_intersect_key($authors, array_flip($model->authorIds)),
        'options' => ['placeholder' => 'Начните вводить имя автора...', 'multiple' => true],
        'bsVersion' => '5',
        'pluginOptions' => [
            'allowClear' => true,
            'minimumInputLength' => 2,
            'ajax' => [
                'url' => \yii\helpers\Url::to(['author/search']),
                'dataType' => 'json',
                'delay' => 250,
                'data' => new \yii\web\JsExpression('function(params) { return {q:params.term, page:params.page}; }'),
                'cache' => true
            ],
        ],
    ]) ?>
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
