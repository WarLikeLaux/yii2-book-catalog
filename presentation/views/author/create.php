<?php

declare(strict_types=1);

use app\presentation\authors\dto\AuthorEditViewModel;
use yii\helpers\Html;

/**
 * @var AuthorEditViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.author_create');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.authors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="author-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $viewModel->form,
    ]) ?>
</div>