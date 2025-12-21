<?php

declare(strict_types=1);

use app\models\forms\SubscriptionForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$form = ActiveForm::begin([
    'id' => 'subscription-form',
    'action' => Url::to(['subscription/subscribe']),
    'options' => ['class' => 'subscription-form'],
]);
?>

<?= Html::hiddenInput('SubscriptionForm[authorId]', $authorId) ?>

<div class="form-group">
    <label>Подписка на автора: <strong><?= Html::encode($author->fio) ?></strong></label>
</div>

<?= $form->field($model, 'phone')->textInput(['placeholder' => '+79001234567']) ?>

<div class="form-group">
    <?= Html::submitButton('Подписаться', ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
$js = <<<JS
$('#subscription-form').on('beforeSubmit', function(e) {
    e.preventDefault();
    var form = $(this);
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(data) {
            if (data.success) {
                alert(data.message);
                $('#sub-modal').modal('hide');
                form[0].reset();
            } else {
                alert(data.message || 'Ошибка при подписке');
            }
        },
        error: function() {
            alert('Ошибка при отправке запроса');
        }
    });
    return false;
});
JS;
$this->registerJs($js);
?>
