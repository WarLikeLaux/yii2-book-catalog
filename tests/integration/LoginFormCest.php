<?php

declare(strict_types=1);

use app\infrastructure\persistence\User;

class LoginFormCest
{
    public function _before(IntegrationTester $I)
    {
        $I->amOnRoute('site/login');
    }

    public function openLoginPage(IntegrationTester $I)
    {
        $I->see('Login', 'h1');
    }

    public function internalLoginById(IntegrationTester $I)
    {
        $I->amLoggedInAs(100);
        $I->amOnPage('/');
        $I->see(Yii::t('app', 'ui.logout', ['username' => 'admin']));
    }

    public function internalLoginByInstance(IntegrationTester $I)
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
        $I->amOnPage('/');
        $I->see(Yii::t('app', 'ui.logout', ['username' => 'admin']));
    }

    public function loginWithEmptyCredentials(IntegrationTester $I)
    {
        $I->submitForm('#login-form', []);
        $I->expectTo('see validations errors');
        $I->see(Yii::t('yii', '{attribute} cannot be blank.', ['attribute' => Yii::t('app', 'ui.username')]));
        $I->see(Yii::t('yii', '{attribute} cannot be blank.', ['attribute' => Yii::t('app', 'ui.password')]));
    }

    public function loginWithWrongCredentials(IntegrationTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'wrong',
        ]);
        $I->expectTo('see validations errors');
        $I->see(Yii::t('app', 'auth.error.invalid_credentials'));
    }

    public function loginSuccessfully(IntegrationTester $I)
    {
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->see(Yii::t('app', 'ui.logout', ['username' => 'admin']));
        $I->dontSeeElement('form#login-form');
    }
}
