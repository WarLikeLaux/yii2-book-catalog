<?php

declare(strict_types=1);

use app\presentation\components\ActiveField;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 */
/**
 * @var app\models\forms\AuthorForm $model
 */
/**
 * @var yii\bootstrap5\ActiveForm $form
 */
?>

<?php $form = ActiveForm::begin([
    'fieldClass' => ActiveField::class,
]); ?>

<?= $form->field($model, 'fio')
    ->textInput(['maxlength' => true])
    ->withRandomGenerator('fio', ['title' => 'Сгенерировать ФИО']) ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'ui.save'), ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>
