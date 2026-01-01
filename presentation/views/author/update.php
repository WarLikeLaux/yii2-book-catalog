<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = Yii::t('app', 'ui.author_update');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.authors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="author-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
