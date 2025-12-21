<?php

namespace tests\unit\models;

use app\models\forms\LoginForm;

class LoginFormTest extends \Codeception\Test\Unit
{
    private $model;

    public function testValidationEmptyFields()
    {
        $this->model = new LoginForm();

        verify($this->model->validate())->false();
        verify($this->model->errors)->arrayHasKey('username');
        verify($this->model->errors)->arrayHasKey('password');
    }

    public function testValidationWrongPassword()
    {
        $this->model = new LoginForm([
            'username' => 'demo',
            'password' => 'wrong_password',
        ]);

        verify($this->model->validate())->false();
        verify($this->model->errors)->arrayHasKey('password');
    }

    public function testValidationCorrectCredentials()
    {
        $this->model = new LoginForm([
            'username' => 'demo',
            'password' => 'demo',
        ]);

        verify($this->model->validate())->true();
        verify($this->model->errors)->arrayHasNotKey('password');
    }

    public function testGetUser()
    {
        $this->model = new LoginForm([
            'username' => 'demo',
        ]);

        $user = $this->model->getUser();
        verify($user)->notNull();
        verify($user->username)->equals('demo');
    }

    public function testGetUserNotFound()
    {
        $this->model = new LoginForm([
            'username' => 'not_existing_user',
        ]);

        $user = $this->model->getUser();
        verify($user)->null();
    }

}
