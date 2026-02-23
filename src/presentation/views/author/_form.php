<?php

declare(strict_types=1);

use app\presentation\components\ActiveField;
use app\presentation\components\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\presentation\authors\forms\AuthorForm $model
 */

?>

<?php $form = ActiveForm::begin([
    'fieldClass' => ActiveField::class,
]); ?>

    <?= $form->errorSummary($model) ?>

    <?= $form->field($model, 'fio')
        ->textInput(['maxlength' => true])
        ->withRandomGenerator('fio', ['title' => Yii::t('app', 'ui.generate_fio')]) ?>

    <div class="form-group"><?= Html::submitButton(Yii::t('app', 'ui.save'), ['class' => 'btn btn-success']) ?></div>

<?php ActiveForm::end(); ?>
