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
        Yii::$app = $this->appBackup;
        parent::tearDown();
    }

    public function testRunReturnsEarlyWhenNoWebApplication(): void
    {
        $app = $this->setConsoleApp();
        $widget = new Alert();

        ob_start();
        $widget->run();
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame($app, Yii::$app);
    }

    private function setConsoleApp(): ConsoleApplication
    {
        return new ConsoleApplication([
            'id' => 'test-console-alert',
            'basePath' => dirname(__DIR__, 5),
        ]);
    }
}
