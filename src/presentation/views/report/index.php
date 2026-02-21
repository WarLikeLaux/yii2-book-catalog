<?php

declare(strict_types=1);

use app\presentation\reports\dto\ReportViewModel;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var ReportViewModel $viewModel
 */

$this->title = Yii::t('app', 'ui.report_title', ['year' => $viewModel->year]);
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="report-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= Url::to(['report/index']) ?>" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="report-year" class="col-form-label">
                        <?= Yii::t('app', 'ui.report_select_year') ?>
                    </label>
                </div>
                <div class="col-auto">
                    <input
                        type="number"
                        name="year"
                        id="report-year"
                        value="<?= Html::encode($viewModel->year) ?>"
                        class="form-control"
                        min="1900"
                        max="2100"
                     />
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><?= Yii::t('app', 'ui.report_show') ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($viewModel->topAuthors === []): ?>
        <div class="alert alert-warning">
            <?= Yii::t('app', 'ui.report_no_data', ['year' => Html::encode($viewModel->year)]) ?>
        </div>
    <?php else: ?>
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th class="col-narrow">#</th>
                    <th><?= Yii::t('app', 'ui.author') ?></th>
                    <th class="col-books-count"><?= Yii::t('app', 'ui.report_books_count') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viewModel->topAuthors as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= Html::encode($row['fio']) ?></td>
                        <td>
                            <strong><?= Html::encode($row['books_count']) ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
