<?php

declare(strict_types=1);

use yii\helpers\ArrayHelper;

return static function (array $params) {
    $commonClosure = require __DIR__ . '/container/common.php';
    $infrastructureClosure = require __DIR__ . '/container/infrastructure.php';
    $repositoriesClosure = require __DIR__ . '/container/repositories.php';
    $servicesClosure = require __DIR__ . '/container/services.php';
    $adaptersClosure = require __DIR__ . '/container/adapters.php';

    $common = $commonClosure($params);
    $infrastructure = $infrastructureClosure($params);
    $repositories = $repositoriesClosure($params);
    $services = $servicesClosure($params);
    $adapters = $adaptersClosure($params);

    return [
        'definitions' => ArrayHelper::merge(
            $common,
            $infrastructure,
            $repositories,
            $services['definitions'] ?? [],
            $adapters['definitions'] ?? [],
        ),
        'singletons' => ArrayHelper::merge(
            $services['singletons'] ?? [],
            $adapters['singletons'] ?? [],
        ),
    ];
};
