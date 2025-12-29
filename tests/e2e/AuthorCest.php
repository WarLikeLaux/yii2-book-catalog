<?php

declare(strict_types=1);

namespace tests\e2e;

final class AuthorCest
{
    public function _before(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('button[name="login-button"]');
        $I->waitForText('Выход', 10);
    }

    public function testAuthorIndexPageWorks(\E2eTester $I): void
    {
        $I->amOnPage('/author/index');
        $I->see('Авторы', 'h1');
        $I->seeElement('.btn-success');
        $I->see('Создать автора');
        $I->seeElement('.grid-view');
    }

    public function testCreateAuthorPageWorks(\E2eTester $I): void
    {
        $I->amOnPage('/author/create');
        $I->see('Создать автора', 'h1');
        $I->seeElement('input[name="AuthorForm[fio]"]');
        $I->seeElement('button[type="submit"]');
    }

    public function testAuthorIndexHasNavigationToBooks(\E2eTester $I): void
    {
        $I->amOnPage('/author/index');
        $I->see('Книги', '.btn-primary');
        $I->click('Книги');
        $I->waitForText('Книги', 5);
        $I->see('Книги', 'h1');
    }
}
