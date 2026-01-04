<?php

declare(strict_types=1);

use app\presentation\components\ActiveField;
use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

/** @var yii\web\View $this */
/** @var app\presentation\books\forms\BookForm $model */
/** @var array $authors */
/** @var yii\bootstrap5\ActiveForm $form */

?>

<?php $form = ActiveForm::begin([
    'fieldClass' => ActiveField::class,
    'options' => ['enctype' => 'multipart/form-data'],
    'enableAjaxValidation' => true,
]); ?>

<?php if (isset($model->version)): ?>
    <?= Html::hiddenInput('BookForm[version]', $model->version) ?>
<?php endif; ?>

<?= $form->field($model, 'title')
    ->textInput(['maxlength' => true])
    ->withRandomGenerator('title', ['title' => 'Сгенерировать название']) ?>

<?= $form->field($model, 'year')
    ->textInput(['type' => 'number'])
    ->withRandomGenerator('year', ['title' => 'Сгенерировать год']) ?>

<?= $form->field($model, 'isbn')
    ->textInput(['maxlength' => true])
    ->withRandomGenerator('isbn', ['title' => 'Сгенерировать ISBN']) ?>

<?= $form->field($model, 'description')
    ->textarea(['rows' => 6])
    ->withRandomGenerator('description', ['title' => 'Сгенерировать описание']) ?>

<?= $form->field($model, 'authorIds')->widget(Select2::class, [
    'initValueText' => $model->getAuthorInitValueText($authors),
    'options' => ['placeholder' => 'Начните вводить имя автора...', 'multiple' => true],
    'bsVersion' => '5',
    'theme' => Select2::THEME_KRAJEE_BS3,
    'pluginOptions' => [
        'allowClear' => true,
        'minimumInputLength' => 2,
        'ajax' => [
            'url' => Url::to(['author/search']),
            'dataType' => 'json',
            'delay' => 250,
            'data' => new JsExpression('function(params) { return {q:params.term, page:params.page}; }'),
            'cache' => true,
        ],
    ],
]) ?>

<?= $form->field($model, 'cover')->fileInput(['accept' => 'image/png,image/jpeg']) ?>

<?php if (isset($book) && $book->coverUrl): ?>
    <div class="form-group">
        <label>Текущая обложка</label><br>
        <?= Html::img($book->coverUrl, ['alt' => $book->title, 'style' => 'max-width: 200px;']) ?>
    </div>
<?php endif; ?>

<div class="form-group">
    <?= Html::submitButton(isset($book) ? 'Сохранить' : 'Создать', ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>
