<?php

declare(strict_types=1);

namespace app\commands;

use app\commands\support\ClassScanner;
use Psy\Shell;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

final class ShellController extends Controller
{
    public function actionIndex(): int
    {
        $scanner = new ClassScanner(
            Yii::getAlias('@app'),
            Yii::$app->params['shell']['aliasTargets'] ?? [],
        );

        [$aliased, $conflicts] = $scanner->scanAndAlias();

        if ($aliased !== []) {
            echo 'Автоалиасы: ' . count($aliased) . PHP_EOL;
        }

        if ($conflicts !== []) {
            echo 'Конфликты алиасов: ' . count($conflicts) . PHP_EOL;
        }

        $shell = new Shell();
        $shell->setScopeVariables([
            'app' => Yii::$app,
            'container' => Yii::$container,
            'db' => Yii::$app->db,
        ]);
        $shell->run();

        return ExitCode::OK;
    }
}
