<?php

declare(strict_types=1);

use app\presentation\components\ActiveForm;
use app\presentation\subscriptions\dto\SubscriptionViewModel;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var SubscriptionViewModel $viewModel
 */

$form = ActiveForm::begin([
    'id' => 'subscription-form',
    'action' => Url::to(['subscription/subscribe']),
    'options' => ['class' => 'subscription-form'],
]);

?>

    <?= Html::hiddenInput('SubscriptionForm[authorId]', $viewModel->author->id) ?>

    <?= $form->errorSummary($viewModel->form) ?>

    <div class="form-group">
        <span class="form-label">
            Подписка на автора:
            <strong><?= Html::encode($viewModel->author->fio) ?></strong>
        </span>
    </div>

    <?= $form->field($viewModel->form, 'phone')->textInput(['placeholder' => '+79001234567']) ?>

    <div class="form-group"><?= Html::submitButton('Подписаться', ['class' => 'btn btn-primary']) ?></div>

<?php ActiveForm::end(); ?>
