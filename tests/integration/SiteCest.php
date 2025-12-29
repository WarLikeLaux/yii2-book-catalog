<?php

declare(strict_types=1);

use app\infrastructure\persistence\User;

final class SiteCest
{
    public function testHomepageWorks(IntegrationTester $I): void
    {
        $I->amOnRoute('site/index');
        $I->seeResponseCodeIs(200);
    }

    public function testLogoutRedirectsToLogin(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
        $I->amOnRoute('site/index');

        $I->sendAjaxPostRequest('/index-test.php?r=site/logout');
        $I->seeResponseCodeIsRedirection();
    }
}
