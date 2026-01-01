<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class FakerAsset extends AssetBundle
{
    public $sourcePath = '@npm/faker/dist';

    public $js = [
        'faker.min.js',
    ];
}
