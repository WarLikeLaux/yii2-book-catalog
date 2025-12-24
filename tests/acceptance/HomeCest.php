<?php

class HomeCest
{
    public function ensureThatHomePageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/site/index');
        $I->see('Библиотека', 'h1');
        $I->seeElement('#book-search-input');
        $I->see('Каталог', 'nav');
    }
}
