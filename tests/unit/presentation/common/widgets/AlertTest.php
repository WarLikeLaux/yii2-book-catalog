<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\widgets;

use app\presentation\common\widgets\Alert;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Application as BaseApplication;
use yii\console\Application as ConsoleApplication;

final class AlertTest extends TestCase
{
    private ?BaseApplication $appBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appBackup = Yii::$app;
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            Yii::$app = $this->appBackup;
        }
    }

    public function testRunReturnsEarlyWhenNoWebApplication(): void
    {
        $app = $this->setConsoleApp();
        $widget = new Alert();

        ob_start();

        try {
            $widget->run();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertSame('', $output);
        $this->assertSame($app, Yii::$app);
    }

    private function setConsoleApp(): ConsoleApplication
    {
        Yii::$app = new ConsoleApplication([
            'id' => 'test-console-alert',
            'basePath' => dirname(__DIR__, 5),
        ]);

        return Yii::$app;
    }
}
