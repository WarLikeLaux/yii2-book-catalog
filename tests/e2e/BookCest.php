<?php

declare(strict_types=1);

namespace tests\e2e;

final class BookCest
{
    public function _before(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('button[name="login-button"]');
        $I->waitForText('Выход', 10);
    }

    public function testBookIndexPageWorks(\E2eTester $I): void
    {
        $I->amOnPage('/book/index');
        $I->see('Книги', 'h1');
        $I->seeElement('.btn-success');
        $I->see('Создать книгу');
        $I->seeElement('.grid-view');
    }

    public function testCreateBookPageWorks(\E2eTester $I): void
    {
        $I->amOnPage('/book/create');
        $I->see('Создать книгу', 'h1');
        $I->seeElement('input[name="BookForm[title]"]');
        $I->seeElement('input[name="BookForm[year]"]');
        $I->seeElement('input[name="BookForm[isbn]"]');
        $I->seeElement('textarea[name="BookForm[description]"]');
    }

    public function testBookIndexHasNavigationToAuthors(\E2eTester $I): void
    {
        $I->amOnPage('/book/index');
        $I->see('Авторы', '.btn-primary');
        $I->click('Авторы');
        $I->waitForText('Авторы', 5);
        $I->see('Авторы', 'h1');
    }

    public function testUnauthorizedUserRedirectsToLogin(\E2eTester $I): void
    {
        $I->click('.logout');
        $I->waitForText('Вход', 10);

        $I->amOnPage('/book/index');
        $I->see('Login', 'h1');
    }
}
