<?php

declare(strict_types=1);

use app\presentation\components\ActiveField;
use app\presentation\components\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * @var yii\web\View $this
 * @var app\presentation\books\forms\BookForm $model
 * @var array $authors
 * @var app\presentation\components\ActiveForm $form
 */

?>

<?php $form = ActiveForm::begin([
    'fieldClass' => ActiveField::class,
    'options' => ['enctype' => 'multipart/form-data'],
    'enableAjaxValidation' => true,
]); ?>

<?php if (isset($model->version)): ?>
    <?= Html::hiddenInput('BookForm[version]', $model->version) ?>
<?php endif; ?>

<?= $form->errorSummary($model) ?>

<?= $form->field($model, 'title')
    ->textInput(['maxlength' => true])
    ->withRandomGenerator('title', ['title' => Yii::t('app', 'ui.generate_title')]) ?>

<?= $form->field($model, 'year')
    ->textInput(['type' => 'number'])
    ->withRandomGenerator('year', ['title' => Yii::t('app', 'ui.generate_year')]) ?>

<?= $form->field($model, 'isbn')
    ->textInput(['maxlength' => true])
    ->withRandomGenerator('isbn', ['title' => Yii::t('app', 'ui.generate_isbn')]) ?>

<?= $form->field($model, 'description')
    ->textarea(['rows' => 6])
    ->withRandomGenerator('description', ['title' => Yii::t('app', 'ui.generate_description')]) ?>

<?= $form->field($model, 'authorIds')->widget(Select2::class, [
    'initValueText' => $model->getAuthorInitValueText($authors),
    'options' => ['placeholder' => Yii::t('app', 'ui.placeholder_authors'), 'multiple' => true],
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
        <label><?= Yii::t('app', 'ui.current_cover') ?></label><br>
        <?= Html::img($book->coverUrl, ['alt' => $book->title, 'style' => 'max-width: 200px;']) ?>
    </div>
<?php endif; ?>

<div class="form-group">
    <?= Html::submitButton(isset($book) ? Yii::t('app', 'ui.save') : Yii::t('app', 'ui.create'), ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>
