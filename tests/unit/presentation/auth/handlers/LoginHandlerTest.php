<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\handlers;

use app\presentation\auth\handlers\LoginHandler;
use Codeception\Test\Unit;
use Yii;
use yii\web\Request;

final class LoginHandlerTest extends Unit
{
    public function testProcessLoginRequestReturnsFalseOnEmptyPost(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('post')->willReturn([]);

        $handler = new LoginHandler();
        $result = $handler->processLoginRequest($request);
        
        $this->assertFalse($result['success']);
    }
}
