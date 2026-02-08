<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        <?= Yii::t('app', 'ui.error_description') ?>
    </p>
    <p>
        <?= Yii::t('app', 'ui.error_contact') ?>
    </p>

</div>
