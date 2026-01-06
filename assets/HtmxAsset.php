<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @link https://htmx.org/
 */
final class HtmxAsset extends AssetBundle
{
    public $sourcePath = '@npm/htmx.org/dist';

    public $js = [
        'htmx.min.js',
    ];
}
