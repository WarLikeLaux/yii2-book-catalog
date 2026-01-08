<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

class HelloController extends Controller
{
    /**
     * Prints the provided message to standard output.
     *
     * @param string $message The message to print; defaults to "hello world".
     * @return int Exit code value (`ExitCode::OK`) indicating successful execution.
     */
    public function actionIndex(string $message = 'hello world'): int
    {
        echo $message . "\n";

        return ExitCode::OK;
    }
}