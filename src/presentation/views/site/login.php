<?php

declare(strict_types=1);

use app\presentation\components\ActiveForm;
use yii\bootstrap5\Html;

/**
 * @var yii\web\View $this
 * @var app\presentation\components\ActiveForm $form
 * @var app\presentation\auth\dto\LoginViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.login');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Yii::t('app', 'ui.login_hint') ?></p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3'],
                    'inputOptions' => ['class' => 'col-lg-3 form-control'],
                    'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
                ],
            ]); ?>
                <?= $form->errorSummary($viewModel->form) ?>
                <?= $form->field($viewModel->form, 'username')->textInput(['autofocus' => true]) ?>
                <?= $form->field($viewModel->form, 'password')->passwordInput() ?>
                <?= $form->field($viewModel->form, 'rememberMe')
                    ->checkbox(['template' => "<div class=\"custom-control custom-checkbox\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>"]) ?>
                <div class="form-group">
                    <div>
                        <?= Html::submitButton(
                            Yii::t('app', 'ui.login'),
                            [
                                'class' => 'btn btn-primary',
                                'name' => 'login-button',
                            ],
                        ) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
            <?php if (YII_ENV_DEV): ?>
                <div class="text-hint">
                    <?= Html::decode(Yii::t('app', 'ui.login_demo_hint', ['admin' => '<strong>admin/admin</strong>', 'demo' => '<strong>demo/demo</strong>'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
