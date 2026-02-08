<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var app\presentation\auth\dto\ApiInfoViewModel $viewModel
 */

use yii\helpers\Html;

$this->title = Yii::t('app', 'ui.api_documentation');
$this->params['breadcrumbs'][] = $this->title;
$scheme = Yii::$app->request->isSecureConnection ? 'https://' : 'http://';
?>
<div class="site-api">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Swagger UI</h5>
                    <p class="card-text text-muted">
                        Интерактивная документация нашего API в формате OpenAPI 3.0. 
                        Здесь вы можете протестировать эндпоинты в реальном времени.
                    </p>
                    <a href="<?= $scheme ?><?= $viewModel->host ?>:<?= $viewModel->swaggerPort ?>" target="_blank" class="btn btn-primary">
                        Открыть Swagger UI
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Base URL</h5>
                    <p class="card-text text-muted">Используйте этот базовый адрес для всех API-запросов:</p>
                    <code class="d-block p-3 bg-light rounded mb-3"><?= $scheme ?><?= $viewModel->host ?>:<?= $viewModel->appPort ?>/api/v1</code>
                    <p class="card-text small text-muted">Доступные эндпоинты:</p>
                    <ul class="small text-muted">
                        <li><code>GET /books</code> — Список книг (с пагинацией)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
