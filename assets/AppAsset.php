<?php

declare(strict_types=1);

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapIconAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';

    public $baseUrl = '@web';

    public $css = [
        'css/site.css',
    ];

    public $js = [
        'js/faker-extensions.js',
        'js/main.js',
        'js/glightbox-init.js',
        'js/catalog.js',
    ];

    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        BootstrapIconAsset::class,
        FakerAsset::class,
        GLightboxAsset::class,
        HtmxAsset::class,
    ];
}
