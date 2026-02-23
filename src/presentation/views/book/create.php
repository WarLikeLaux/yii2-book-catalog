<?php

declare(strict_types=1);

use app\presentation\books\dto\BookEditViewModel;
use yii\helpers\Html;

/**
 * @var BookEditViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.book_create');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ui.books'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render(
        '_form',
        [
            'model' => $viewModel->form,
            'authors' => $viewModel->authors,
            'book' => $viewModel->book,
        ],
    ) ?>
</div>
