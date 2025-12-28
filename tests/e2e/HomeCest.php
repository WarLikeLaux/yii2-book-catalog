<?php

declare(strict_types=1);

namespace tests\e2e;

final class HomeCest
{
    public function ensureThatHomePageWorks(\E2eTester $I): void
    {
        $I->amOnPage('/site/index');
        $I->see('Библиотека', 'h1');
        $I->seeElement('#book-search-input');
        $I->see('Каталог', 'nav');
    }
}
