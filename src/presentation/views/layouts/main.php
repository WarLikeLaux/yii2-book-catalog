<?php

declare(strict_types=1);

use app\assets\AppAsset;
use app\presentation\common\widgets\Alert;
use app\presentation\common\widgets\SystemInfoWidget;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Url;

/**
 * @var View $this
 * @var string $content
 */

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">

    <head>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
    </head>

    <body class="d-flex flex-column h-100">
        <?php $this->beginBody() ?>
        <header id="header">
            <?php NavBar::begin([
                'brandLabel' => Yii::$app->name,
                'brandUrl' => Yii::$app->homeUrl,
                'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top'],
            ]);
            $menuItems = [
                ['label' => Yii::t('app', 'ui.catalog'), 'url' => ['/site/index']],
                ['label' => Yii::t('app', 'ui.report'), 'url' => ['/report/index']],
                ['label' => 'API', 'url' => ['/site/api']],
            ];
            if (!Yii::$app->user->isGuest) {
                $menuItems[] = ['label' => Yii::t('app', 'ui.books'), 'url' => ['/book/index']];
                $menuItems[] = ['label' => Yii::t('app', 'ui.authors'), 'url' => ['/author/index']];
                if (YII_ENV_DEV) {
                    $menuItems[] = [
                        'label' => Yii::t('app', 'ui.traces'),
                        'url' => (Yii::$app->request->isSecureConnection ? 'https://' : 'http://') . Yii::$app->request->serverName . ':' . Yii::$app->params['jaegerUiPort'],
                        'linkOptions' => ['target' => '_blank'],
                    ];
                }
            }
            $menuItems[] = Yii::$app->user->isGuest
                ? ['label' => Yii::t('app', 'ui.login'), 'url' => ['/site/login']]
                : '<li class="nav-item">' . Html::beginForm(['/site/logout']) . Html::submitButton(Yii::t('app', 'ui.logout', ['username' => Yii::$app->user->identity->username]), ['class' => 'nav-link btn btn-link logout']) . Html::endForm() . '</li>';
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $menuItems,
            ]);
            echo SystemInfoWidget::widget();
            NavBar::end(); ?>
        </header>
        <main id="main" class="flex-shrink-0" role="main">
            <div class="container">
                <?php $breadcrumbs = $this->params['breadcrumbs'] ?? [] ?>
                <?php if ($breadcrumbs !== []): ?>
                    <?= Breadcrumbs::widget([
                        'homeLink' => ['label' => Yii::t('app', 'ui.catalog'), 'url' => Yii::$app->homeUrl],
                        'links' => $breadcrumbs,
                    ]) ?>
                <?php endif ?>
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </main>
        <footer id="footer" class="footer mt-auto">
            <div class="container">
                <div class="row gy-4">
                    <div class="col-md-6 text-center text-md-start">
                        <div class="mb-3">
                            <?= Html::a(
                                'Yii 2 Book Catalog',
                                Yii::$app->homeUrl,
                                ['class' => 'fw-bold text-white text-decoration-none h5 footer-brand'],
                            ) ?>
                            <div class="text-white-50 small mt-1">
                                <?= Html::decode(Yii::t('app', 'ui.footer_subtitle')) ?>
                            </div>
                        </div>
                        <p class="text-white-50 small mb-0 footer-desc">
                            <?= Html::decode(Yii::t('app', 'ui.footer_description')) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 text-center text-md-end">
                                <h6 class="text-white text-uppercase small fw-bold opacity-75 mb-3">
                                    <?= Yii::t('app', 'ui.footer_project') ?>
                                </h6>
                                <ul class="list-unstyled mb-0 d-grid gap-2">
                                    <li>
                                        <a
                                            href="https://github.com/WarLikeLaux/yii2-book-catalog"
                                            target="_blank"
                                            class="text-white-50 text-decoration-none hover-white small"
                                        >
                                            <i class="bi bi-github me-1"></i>
                                            <?= Yii::t('app', 'ui.footer_repo') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="<?= Url::to(['/site/api']) ?>"
                                            class="text-white-50 text-decoration-none hover-white small"
                                        >
                                            <i class="bi bi-code-slash me-1"></i>
                                            OpenAPI (Swagger)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-6 text-center text-md-end">
                                <h6 class="text-white text-uppercase small fw-bold opacity-75 mb-3">
                                    <?= Yii::t('app', 'ui.footer_resources') ?>
                                </h6>
                                <ul class="list-unstyled mb-0 d-grid gap-2">
                                    <li>
                                        <a
                                            href="https://refactoring.guru/ru/design-patterns"
                                            target="_blank"
                                            class="text-white-50 text-decoration-none hover-white small"
                                        >
                                            <i class="bi bi-diagram-3 me-1"></i>
                                            <?= Yii::t('app', 'ui.footer_patterns') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="https://phptherightway.com/"
                                            target="_blank"
                                            class="text-white-50 text-decoration-none hover-white small"
                                        >
                                            <i class="bi bi-check2-circle me-1"></i>
                                            PHP: The Right Way
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="https://www.domainlanguage.com/ddd/"
                                            target="_blank"
                                            class="text-white-50 text-decoration-none hover-white small"
                                        >
                                            <i class="bi bi-book me-1"></i>
                                            DDD Community
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="border-top border-secondary border-opacity-25 mt-4 pt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2"
                >
                    <div class="small text-white-50">&copy; 2025 &mdash;<?= date('Y') ?></div>
                    <div class="small text-white-50 font-monospace"><?= $this->params['requestId'] ?? '' ?></div>
                </div>
            </div>
        </footer>
        <?php $this->endBody() ?>
    </body>

</html>
<?php $this->endPage() ?>
