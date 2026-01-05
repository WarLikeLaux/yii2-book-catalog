<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\handlers;

use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewDataFactory;
use Codeception\Test\Unit;

final class AuthViewDataFactoryTest extends Unit
{
    public function testCreateLoginFormReturnsLoginForm(): void
    {
        $factory = new AuthViewDataFactory();

        $form = $factory->createLoginForm();

        $this->assertInstanceOf(LoginForm::class, $form);
        $this->assertSame('', $form->username);
        $this->assertSame('', $form->password);
        $this->assertTrue($form->rememberMe);
    }
}
