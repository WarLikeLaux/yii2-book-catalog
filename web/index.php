<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/bootstrap_env.php';

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

require __DIR__ . '/../config/bootstrap.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
