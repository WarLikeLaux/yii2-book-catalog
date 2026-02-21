<?php

declare(strict_types=1);

use app\presentation\authors\dto\AuthorEditViewModel;
use yii\helpers\Html;

/**
 * @var AuthorEditViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.author_update');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.authors'), 'url' => ['index']];

if ($viewModel->author !== null) {
    $this->params['breadcrumbs'][] = ['label' => $viewModel->author->fio, 'url' => ['view', 'id' => $viewModel->author->id]];
}

$this->params['breadcrumbs'][] = $this->title;

?>

<div class="author-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $viewModel->form]) ?>
</div>
