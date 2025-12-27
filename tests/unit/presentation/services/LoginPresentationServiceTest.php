<?php

declare(strict_types=1);

namespace app\tests\unit\presentation\services;

use app\presentation\services\LoginPresentationService;
use Codeception\Test\Unit;
use Yii;
use yii\web\Request;

final class LoginPresentationServiceTest extends Unit
{
    public function testProcessLoginRequestReturnsFalseOnEmptyPost(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('post')->willReturn([]);

        $service = new LoginPresentationService();
        $result = $service->processLoginRequest($request);
        
        $this->assertFalse($result['success']);
    }
}