<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\presentation\common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

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
        <?php
        NavBar::begin([
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top'],
        ]);
        $menuItems = [
            ['label' => 'Каталог', 'url' => ['/site/index']],
            ['label' => 'Отчет', 'url' => ['/report/index']],
            ['label' => 'API', 'url' => ['/site/api']],
        ];

        if (!Yii::$app->user->isGuest) {
            $menuItems[] = ['label' => 'Книги', 'url' => ['/book/index']];
            $menuItems[] = ['label' => 'Авторы', 'url' => ['/author/index']];
            if (YII_ENV_DEV) {
                $menuItems[] = [
                    'label' => 'Логи',
                    'url' => 'http://' . Yii::$app->request->serverName . ':' . Yii::$app->params['buggregatorUiPort'],
                    'linkOptions' => ['target' => '_blank'],
                ];
            }
        }

        $menuItems[] = Yii::$app->user->isGuest
            ? ['label' => 'Вход', 'url' => ['/site/login']]
            : '<li class="nav-item">'
            . Html::beginForm(['/site/logout'])
            . Html::submitButton(
                'Выход (' . Yii::$app->user->identity->username . ')',
                ['class' => 'nav-link btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';

        echo Nav::widget([
            'options' => ['class' => 'navbar-nav'],
            'items' => $menuItems,
        ]);
        echo Html::tag(
            'span',
            'DB: ' . strtoupper((string) Yii::$app->db->driverName),
            ['class' => 'navbar-text ms-3 small text-warning']
        );
        NavBar::end();
        ?>
    </header>

    <main id="main" class="flex-shrink-0" role="main">
        <div class="container">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?= Breadcrumbs::widget([
                    'homeLink' => ['label' => 'Каталог', 'url' => Yii::$app->homeUrl],
                    'links' => $this->params['breadcrumbs'],
                ]) ?>
            <?php endif ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    </main>

    <footer id="footer" class="mt-auto py-3 bg-light">
        <div class="container">
            <div class="row text-muted">
                <div class="col-md-6 text-center text-md-start">&copy; Yii 2 Book Catalog <?= date('Y') ?></div>
                <div class="col-md-6 text-center text-md-end"><?= Yii::powered() ?></div>
            </div>
        </div>
    </footer>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>
