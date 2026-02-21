<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;

final class SubscriptionFormCest
{
    public function _before(IntegrationTester $_I): void
    {
        DbCleaner::clear(['authors']);
    }

    public function testSubscribeValidationError(IntegrationTester $I): void
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

    public function testSubscribeWithInvalidPhone(IntegrationTester $I): void
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

    public function testSubscribeWithNonExistentAuthor(IntegrationTester $I): void
    {
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '+79991234567',
            'SubscriptionForm[authorId]' => 99999,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
        $I->assertArrayHasKey('errors', $response);
        $I->assertArrayHasKey('authorId', $response['errors']);
        $I->assertIsArray($response['errors']['authorId']);
        $I->assertNotEmpty($response['errors']['authorId']);
    }

    public function testSubscribeWithZeroAuthorId(IntegrationTester $I): void
    {
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '+79991234567',
            'SubscriptionForm[authorId]' => 0,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
    }

    public function testSubscribeWithNegativeAuthorId(IntegrationTester $I): void
    {
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '+79991234567',
            'SubscriptionForm[authorId]' => -1,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
    }

    public function testSubscribeWithValidPhoneFormatsIt(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Phone Format Author']);

        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '+7 999 123-45-67',
            'SubscriptionForm[authorId]' => $authorId,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertTrue($response['success']);
    }

    public function testSubscribeWithUnparseablePhone(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Unparseable Phone Author']);

        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => 'not-a-phone',
            'SubscriptionForm[authorId]' => $authorId,
        ]);

        $response = json_decode($I->grabPageSource(), true);
        $I->assertFalse($response['success']);
        $I->assertArrayHasKey('errors', $response);
        $I->assertArrayHasKey('phone', $response['errors']);
        $I->assertIsArray($response['errors']['phone']);
        $I->assertNotEmpty($response['errors']['phone']);
    }
}
