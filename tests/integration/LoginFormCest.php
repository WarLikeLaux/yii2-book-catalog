<?php

use app\infrastructure\persistence\User;

class LoginFormCest
{
    public function _before(\IntegrationTester $I)
    {
        $I->amOnRoute('site/login');
    }

    public function openLoginPage(\IntegrationTester $I)
    {
        $I->see('Login', 'h1');

    }

    public function internalLoginById(\IntegrationTester $I)
    {
        $I->amLoggedInAs(100);
        $I->amOnPage('/');
        $I->see('Выход (admin)');
    }

    public function internalLoginByInstance(\IntegrationTester $I)
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
        $I->amOnPage('/');
        $I->see('Выход (admin)');
    }

    public function loginWithEmptyCredentials(\IntegrationTester $I)
    {
        $I->submitForm('#login-form', []);
        $I->expectTo('see validations errors');
        $I->see('Необходимо заполнить «Имя пользователя».');
        $I->see('Необходимо заполнить «Пароль».');
    }

    public function loginWithWrongCredentials(\IntegrationTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'wrong',
        ]);
        $I->expectTo('see validations errors');
        $I->see('Неверный логин или пароль.');
    }

    public function loginSuccessfully(\IntegrationTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->see('Выход (admin)');
        $I->dontSeeElement('form#login-form');
    }
}