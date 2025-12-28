<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\forms;

use app\infrastructure\persistence\User;
use app\presentation\auth\forms\LoginForm;
use Codeception\Test\Unit;

final class LoginFormTest extends Unit
{
    public function testRules(): void
    {
        $form = new LoginForm();
        $this->assertIsArray($form->rules());
    }

    public function testLabels(): void
    {
        $form = new LoginForm();
        $this->assertIsArray($form->attributeLabels());
    }

    public function testValidatePasswordValid(): void
    {
        $form = new LoginForm();
        $form->username = 'admin';
        $form->password = 'admin';

        $form->validatePassword('password');

        $this->assertFalse($form->hasErrors('password'));
    }

    public function testValidatePasswordInvalid(): void
    {
        $form = new LoginForm();
        $form->username = 'admin';
        $form->password = 'wrongpass';

        $form->validatePassword('password');

        $this->assertTrue($form->hasErrors('password'));
    }

    public function testValidatePasswordNonExistentUser(): void
    {
        $form = new LoginForm();
        $form->username = 'nouser';
        $form->password = 'any';

        $form->validatePassword('password');

        $this->assertTrue($form->hasErrors('password'));
    }

    public function testValidatePasswordWithErrors(): void
    {
        $form = new LoginForm();
        $form->addError('username', 'Some error');
        $form->validatePassword('password');
        $this->assertFalse($form->hasErrors('password'));
    }

    public function testGetUser(): void
    {
        $form = new LoginForm();
        $form->username = 'admin';
        $user = $form->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('admin', $user->username);
        $this->assertSame($user, $form->getUser());
    }
}
