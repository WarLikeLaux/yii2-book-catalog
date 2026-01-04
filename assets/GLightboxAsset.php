<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;

class GLightboxAsset extends AssetBundle
{
    public $sourcePath = '@npm/glightbox/dist';

    public $css = [
        'css/glightbox.min.css',
    ];

    public $js = [
        'js/glightbox.min.js',
    ];

    public $depends = [
        YiiAsset::class,
    ];
}
