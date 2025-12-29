<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\auth\forms\LoginForm;
use Codeception\Test\Unit;

final class LoginFormTest extends Unit
{
    public function testValidatePasswordSkipsWhenFormHasErrors(): void
    {
        $form = new LoginForm();
        $form->username = 'admin';
        $form->password = 'wrong';
        $form->addError('username', 'Some previous error');

        $form->validatePassword('password');

        $this->assertFalse($form->hasErrors('password'));
        $this->assertTrue($form->hasErrors('username'));
    }
}
