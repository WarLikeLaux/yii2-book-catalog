<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;

final class SubscriptionFormCest
{
    public function testSubscribeValidationError(\IntegrationTester $I): void
    {
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => 'invalid',
            'SubscriptionForm[authorId]' => 999,
        ]);

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
        $I->assertArrayHasKey('errors', $response);
    }

    public function testSubscribeWithInvalidPhone(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '12345',
            'SubscriptionForm[authorId]' => $authorId,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
    }
}
