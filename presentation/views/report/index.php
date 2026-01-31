<?php

declare(strict_types=1);

use app\presentation\reports\dto\ReportViewModel;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ReportViewModel $viewModel */

$this->title = 'ТОП-10 авторов за ' . (string)$viewModel->year . ' год';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="report-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= Url::to(['report/index']) ?>" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label">Выберите год:</label>
                </div>
                <div class="col-auto">
                    <input type="number" name="year" value="<?= Html::encode($viewModel->year) ?>" class="form-control" min="1900" max="2100">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Показать</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($viewModel->topAuthors === []): ?>
        <div class="alert alert-warning">
            Нет данных о книгах за <?= Html::encode($viewModel->year) ?> год. Попробуйте сгенерировать данные командой <code>php yii seed</code>.
        </div>
    <?php else: ?>
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px">#</th>
                    <th>Автор</th>
                    <th style="width: 150px">Книг выпущено</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viewModel->topAuthors as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= Html::encode($row['fio']) ?></td>
                        <td><strong><?= Html::encode($row['books_count']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
