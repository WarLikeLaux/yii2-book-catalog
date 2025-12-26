<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\services\notifications\FlashNotificationService;
use Codeception\Test\Unit;
use Yii;

final class FlashNotificationServiceTest extends Unit
{
    private FlashNotificationService $service;

    protected function _before(): void
    {
        $this->service = new FlashNotificationService();
    }

    public function testSuccess(): void
    {
        $this->service->success('Success message');
        $this->assertSame('Success message', Yii::$app->session->getFlash('success'));
    }

    public function testError(): void
    {
        $this->service->error('Error message');
        $this->assertSame('Error message', Yii::$app->session->getFlash('error'));
    }

    public function testInfo(): void
    {
        $this->service->info('Info message');
        $this->assertSame('Info message', Yii::$app->session->getFlash('info'));
    }

    public function testWarning(): void
    {
        $this->service->warning('Warning message');
        $this->assertSame('Warning message', Yii::$app->session->getFlash('warning'));
    }
}
