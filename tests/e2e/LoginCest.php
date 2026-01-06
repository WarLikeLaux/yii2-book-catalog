<?php

declare(strict_types=1);

namespace tests\e2e;

final class LoginCest
{
    public function _before(\E2eTester $I): void
    {
        $I->amOnPage('/site/logout');
    }

    public function testLoginPageDisplaysForm(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->see('Login', 'h1');
        $I->seeElement('#login-form');
        $I->seeElement('input[name="LoginForm[username]"]');
        $I->seeElement('input[name="LoginForm[password]"]');
        $I->seeElement('button[name="login-button"]');
    }

    public function testSuccessfulLogin(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('button[name="login-button"]');

        $I->waitForElement('.logout', 10);
        $I->seeElement('.logout');
        $I->dontSee('Вход', 'nav');
    }

    public function testFailedLoginShowsError(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'wrong');
        $I->fillField('LoginForm[password]', 'wrong');
        $I->click('button[name="login-button"]');

        $I->wait(1);
        $I->see('Неверный логин или пароль.');
    }

    public function testLogoutWorks(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('button[name="login-button"]');

        $I->waitForElement('.logout', 10);
        $I->click('.logout');

        $I->waitForText('Вход', 10);
        $I->see('Вход', 'nav');
    }
}
