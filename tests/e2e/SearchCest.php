<?php

declare(strict_types=1);

namespace tests\e2e;

final class SearchCest
{
    public function testSearchInputExistsOnHomepage(\E2eTester $I): void
    {
        $I->amOnPage('/site/index');
        $I->see('Библиотека', 'h1');
        $I->seeElement('#book-search-input');
        $I->seeElement('#book-list-pjax');
    }

    public function testSearchPlaceholderText(\E2eTester $I): void
    {
        $I->amOnPage('/site/index');
        $I->seeElement('#book-search-input[placeholder*="Название"]');
    }

    public function testBookCardsDisplayedOnHomepage(\E2eTester $I): void
    {
        $I->amOnPage('/site/index');
        $I->seeElement('.card');
        $I->seeElement('.card-title');
    }

    public function testLoggedInUserSeesManagementButton(\E2eTester $I): void
    {
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('button[name="login-button"]');
        $I->waitForText('Выход', 10);

        $I->amOnPage('/site/index');
        $I->see('Управление книгами');
    }

    public function testGuestUserDoesNotSeeManagementButton(\E2eTester $I): void
    {
        $I->amOnPage('/site/index');
        $I->dontSee('Управление книгами');
    }
}
