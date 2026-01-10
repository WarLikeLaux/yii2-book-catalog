<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiAuthAdapter;
use Codeception\Test\Unit;
use Yii;
use yii\base\Application as BaseApplication;
use yii\console\Application as ConsoleApplication;

final class YiiAuthAdapterTest extends Unit
{
    private ?BaseApplication $appBackup = null;
    private YiiAuthAdapter $adapter;

    protected function _before(): void
    {
        $this->appBackup = Yii::$app;
        $this->adapter = new YiiAuthAdapter();
    }

    protected function _after(): void
    {
        Yii::$app = $this->appBackup;
    }

    public function testIsGuestReturnsTrueWhenNoWebApplication(): void
    {
        $this->setConsoleApp();

        $this->assertTrue($this->adapter->isGuest());
    }

    public function testLoginReturnsFalseWhenNoWebApplication(): void
    {
        $this->setConsoleApp();

        $this->assertFalse($this->adapter->login('admin', 'admin', false));
    }

    public function testLogoutDoesNothingWhenNoWebApplication(): void
    {
        $app = $this->setConsoleApp();

        $this->adapter->logout();

        $this->assertSame($app, Yii::$app);
    }

    private function setConsoleApp(): ConsoleApplication
    {
        return new ConsoleApplication([
            'id' => 'test-console-auth',
            'basePath' => dirname(__DIR__, 4),
        ]);
    }
}
