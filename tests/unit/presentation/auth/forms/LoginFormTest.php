<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\forms;

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

    public function testValidatePassword(): void
    {
        // Valid password
        $form = new LoginForm();
        $form->username = 'admin';
        $form->password = 'admin';
        
        $form->validatePassword('password');
        $this->assertFalse($form->hasErrors('password'));
        
        // Invalid password
        $form = new LoginForm();
        $form->username = 'admin';
        $form->password = 'wrongpass';
        
        $form->validatePassword('password');
        $this->assertTrue($form->hasErrors('password'));
        
        // Non-existent user
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
        $this->assertFalse($form->hasErrors('password')); // Should return early
    }

    public function testGetUser(): void
    {
        $form = new LoginForm();
        $form->username = 'admin';
        $user = $form->getUser();
        $this->assertInstanceOf(\app\infrastructure\persistence\User::class, $user);
        $this->assertSame('admin', $user->username);
        
        // Cached call
        $this->assertSame($user, $form->getUser());
    }
}
